import { chromium } from '@playwright/test'
import assert from 'node:assert/strict'
import { base, sql, publicApi, slots, createVerifiedRider, signInRider } from './support.mjs'
const browser=await chromium.launch({headless:true})
const page=await browser.newPage()
const runtime=[];page.on('pageerror',e=>runtime.push(e.message))
let passed=0
const pass=s=>{passed++;console.log('PASS:',s)}
const date='2099-01-06'
const riderEmail=`availability-browser-${Date.now()}@example.com`
const rpc=(lesson='lesson-1')=>slots(lesson,date)
const SLOTS='**/api/availability/slots*'
async function reloadDate(){await page.locator('input[type=date]').first().fill('');await page.locator('input[type=date]').first().fill(date);await page.getByRole('button',{name:'2:00 PM',exact:true}).waitFor()}
try {
 const normal=await rpc();assert(normal.some(s=>s.start_time==='14:00'));pass('anonymous HTTP slot lookup returns real slots')
 for(const path of ['instructor-availability','horse-availability','blocked-times','bookings','customers','riders','horses']){const {status}=await publicApi('/admin/'+path);assert.equal(status,401);pass('anonymous HTTP cannot read '+path)}
 await page.goto(`${base}/book`)
 await page.waitForURL('**/rider/sign-in?returnTo=/book')
 assert.equal(await page.getByRole('button',{name:/Walk & Trot/}).count(),0)
 createVerifiedRider(riderEmail)
 await signInRider(page,riderEmail)
 await page.waitForURL('**/book')
 pass('booking requires sign-in first and returns to the wizard after password login')
 await page.getByRole('button',{name:/Walk & Trot/}).click();await page.getByRole('button',{name:'Next',exact:true}).click()
 await page.locator('input[type=date]').first().fill(date)
 await page.getByRole('button',{name:'2:00 PM',exact:true}).click()
 assert.match(await page.locator('body').innerText(),/Selected time: 2:00 PM/);pass('real wizard slots and selected time')
 await page.locator('input[type=date]').first().fill('2099-01-08');await page.getByRole('button',{name:'2:00 PM',exact:true}).waitFor();assert(! (await page.locator('body').innerText()).includes('Selected time:'));pass('date clears selection')
 await page.getByRole('button',{name:'Back',exact:true}).click();await page.getByRole('button',{name:/Sibling\/Joined/}).click();await page.getByRole('button',{name:'Next',exact:true}).click();await reloadDate();pass('joined lesson with two horses produces slot')
 sql("INSERT INTO blocked_times(id,date,start_time,end_time) VALUES ('verify-instructor','2099-01-06','14:00','15:00')")
 assert(!(await rpc()).some(s=>s.start_time==='14:00'));await page.locator('input[type=date]').first().fill('');await page.locator('input[type=date]').first().fill(date);await page.getByRole('button',{name:'3:00 PM',exact:true}).waitFor();assert.equal(await page.getByRole('button',{name:'2:00 PM',exact:true}).count(),0);pass('instructor block removes slot in browser')
 sql("DELETE FROM blocked_times WHERE id='verify-instructor'");await reloadDate();pass('removing instructor block restores slot')
 sql("INSERT INTO bookings(id,customer_id,lesson_type_id,horse_id,start_time,end_time,status) VALUES ('verify-booking','customer-1','lesson-1','horse-1','2099-01-06 14:00','2099-01-06 15:00','confirmed')")
 assert(!(await rpc()).some(s=>s.start_time==='14:00'));pass('booking conflict removes slot')
 sql("UPDATE bookings SET status='cancelled' WHERE id='verify-booking'");assert((await rpc()).some(s=>s.start_time==='14:00'));await reloadDate();pass('cancelled booking restores slot')
 sql("INSERT INTO blocked_times(id,horse_id,date,start_time,end_time) VALUES ('verify-horse','horse-2','2099-01-06','14:00','15:00'),('verify-horse-extra','horse-3','2099-01-06','14:00','15:00')")
 assert(!(await rpc('lesson-4')).some(s=>s.start_time==='14:00'));await page.locator('input[type=date]').first().fill('');await page.locator('input[type=date]').first().fill(date);await page.getByRole('button',{name:'3:00 PM',exact:true}).waitFor();assert.equal(await page.getByRole('button',{name:'2:00 PM',exact:true}).count(),0);pass('joined lesson with only one available horse disappears')
 sql("DELETE FROM blocked_times WHERE id IN ('verify-horse','verify-horse-extra')");await reloadDate();pass('restoring second horse restores joined slot')
 await page.getByRole('button',{name:'2:00 PM',exact:true}).click();await page.getByRole('button',{name:'Back',exact:true}).click();await page.getByRole('button',{name:/Walk & Trot/}).click();await page.getByRole('button',{name:'Next',exact:true}).click();await page.getByRole('button',{name:'2:00 PM',exact:true}).waitFor();assert(!(await page.locator('body').innerText()).includes('Selected time:'));pass('lesson clears selection')
 await page.locator('input[type=date]').first().fill('2099-01-10');await page.getByText('No available times for this date.',{exact:false}).waitFor();pass('no availability state')
 await page.route(SLOTS,route=>route.fulfill({status:500,contentType:'application/json',body:JSON.stringify({message:'test failure'})}));await page.locator('input[type=date]').first().fill(date);await page.getByRole('alert').waitFor();pass('API error state')
 await page.unroute(SLOTS);await page.getByRole('button',{name:'Try again'}).click();await page.getByRole('button',{name:'2:00 PM',exact:true}).waitFor();pass('retry restores real availability')
 await page.route(SLOTS,async route=>{await new Promise(r=>setTimeout(r,400));await route.continue()});await page.locator('input[type=date]').first().fill('2099-01-08');await page.getByText('Loading available times…').waitFor();pass('loading state');await page.getByRole('button',{name:'2:00 PM',exact:true}).waitFor();await page.unroute(SLOTS)
 await page.reload();await page.getByRole('button',{name:/Walk & Trot/}).waitFor();pass('public router refresh')
 assert.deepEqual(runtime,[]);pass('no browser runtime exceptions')
 console.log(`Browser/HTTP: ${passed} passed, 0 failed, 0 skipped`)
} finally {
 sql("DELETE FROM blocked_times WHERE id IN ('verify-instructor','verify-horse','verify-horse-extra'); DELETE FROM bookings WHERE id='verify-booking'")
 sql(`DELETE FROM customers WHERE email='${riderEmail}'; DELETE FROM users WHERE email='${riderEmail}'`)
 await browser.close()
}
