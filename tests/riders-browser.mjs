import { chromium } from '@playwright/test'
import assert from 'node:assert/strict'
import { base, sql, slots, createAdmin, deleteAdmin } from './support.mjs'
// Public wizard renders one rider form per lesson_types.required_rider_count and books them all.
const lessons=sql("SELECT id,name,required_rider_count FROM lesson_types WHERE active=1 ORDER BY id").trim().split('\n').map(l=>{const [id,name,n]=l.split('\t');return {id,name,required_rider_count:Number(n)}})
const single=lessons.find(l=>l.required_rider_count===1),multi=lessons.find(l=>l.required_rider_count===2)
assert(single&&multi,'need an active 1-rider and 2-rider lesson type')
const slotsOn=async(lesson,date)=>(await slots(lesson,date))||[]
let date
for(let i=1;i<=28&&!date;i++){const d=new Date();d.setDate(d.getDate()+i);const s=d.toISOString().slice(0,10);if((await slotsOn(single.id,s)).length&&(await slotsOn(multi.id,s)).length)date=s}
assert(date,'no date in the next 4 weeks with slots for both lessons')
const tag=Date.now(),email=`riders-${tag}@example.com`,adminEmail=`riders-admin-${tag}@example.com`,password='Local-test-riders!7'
const browser=await chromium.launch({headless:true}),page=await browser.newPage();const runtime=[];page.on('pageerror',e=>runtime.push(e.message))
let passed=0,admin=false;const pass=s=>{passed++;console.log('PASS:',s)}
const wizardText=()=>page.locator('div.bg-white.rounded-lg.shadow-lg').first().innerText()
const next=()=>page.getByRole('button',{name:'Next',exact:true})
const riderBox=n=>page.locator('div.border-l-4').filter({has:page.getByRole('heading',{name:`Rider ${n}`,exact:true})})
const toRiderStep=async lesson=>{await page.getByRole('button',{name:new RegExp(lesson.name.replace(/[/&]/g,'\\$&'))}).click();await next().click();await page.locator('input[type=date]').first().fill(date);await page.locator('[aria-pressed]').first().click();await next().click();await page.getByRole('heading',{name:'Contact & Rider Information'}).waitFor()}
try{
 await page.goto(`${base}/book`)
 await toRiderStep(single)
 assert.equal(await page.locator('div.border-l-4').count(),1);await riderBox(1).waitFor();pass(`${single.name} (1 rider) shows exactly one rider form`)
 for(let i=0;i<2;i++)await page.getByRole('button',{name:'Back',exact:true}).click()
 await toRiderStep(multi)
 assert.equal(await page.locator('div.border-l-4').count(),2);await riderBox(1).waitFor();await riderBox(2).waitFor();pass(`${multi.name} (2 riders) shows exactly two rider forms`)
 await page.getByPlaceholder('John',{exact:true}).fill('Pat');await page.getByPlaceholder('john@example.com').fill(email)
 await page.locator('input[type=text]').nth(1).fill('Parent'+tag)
 await riderBox(1).getByLabel('I am the rider').check();await riderBox(1).locator('input[type=date]').fill('1985-02-03')
 await riderBox(2).locator('input[type=text]').nth(0).fill('Sam')
 assert.equal(await next().isDisabled(),true);pass('incomplete Rider 2 blocks Continue')
 await riderBox(2).locator('input[type=text]').nth(1).fill('Kid'+tag);assert.equal(await next().isDisabled(),true)
 await riderBox(2).locator('input[type=date]').fill('2014-06-07');assert.equal(await next().isDisabled(),false);pass('both riders complete enables Continue')
 await next().click();const review=page.locator('div.rounded-lg.border-border-light').filter({has:page.getByRole('heading',{name:'Riders',exact:true})})
 await review.getByText(`Pat Parent${tag}`).waitFor();await review.getByText(`Sam Kid${tag}`).waitFor();await review.getByText('2014-06-07').waitFor();await review.getByText('1985-02-03').waitFor();assert.doesNotMatch(await wizardText(),/horse/i);pass('Review shows both riders with DOB, no horse details')
 await page.getByRole('button',{name:'Book Lesson',exact:true}).click();await page.getByText('Your Lesson Has Been Booked!').waitFor()
 const [bookingCount,riderLinks,horseCount]=sql(`SELECT COUNT(DISTINCT b.id),(SELECT COUNT(*) FROM booking_riders br JOIN bookings b2 ON b2.id=br.booking_id JOIN customers c2 ON c2.id=b2.customer_id WHERE c2.email='${email}'),(SELECT COUNT(DISTINCT bh.horse_id) FROM booking_horses bh JOIN bookings b3 ON b3.id=bh.booking_id JOIN customers c3 ON c3.id=b3.customer_id WHERE c3.email='${email}') FROM bookings b JOIN customers c ON c.id=b.customer_id WHERE c.email='${email}'`).trim().split('\t').map(Number)
 assert.equal(bookingCount,1);assert.equal(riderLinks,2);assert.equal(horseCount,2)
 pass('one booking, two booking_riders, two distinct horses assigned automatically')
 assert.doesNotMatch(await wizardText(),/horse/i);pass('confirmation shows no horse assignment')
 createAdmin(adminEmail,password);admin=true
 await page.goto(`${base}/admin/login`);await page.locator('input[type=email]').fill(adminEmail);await page.locator('input[type=password]').fill(password);await page.getByRole('button',{name:'Login',exact:true}).click();await page.waitForURL('**/admin/dashboard')
 await page.goto(`${base}/admin/bookings`);await page.locator('tr').filter({hasText:`Pat Parent${tag}, Sam Kid${tag}`}).waitFor();pass('Admin shows the one booking with both riders')
 assert.deepEqual(runtime,[]);console.log(`Riders browser: ${passed} passed, 0 failed`)
}finally{
 await browser.close()
 sql(`DELETE b FROM bookings b JOIN customers c ON c.id=b.customer_id WHERE c.email='${email}'; DELETE r FROM riders r JOIN customers c ON c.id=r.customer_id WHERE c.email='${email}'; DELETE FROM customers WHERE email='${email}'`)
 if(admin)deleteAdmin(adminEmail)
}
