import { chromium } from '@playwright/test'
import assert from 'node:assert/strict'
import { base, sql, slots, createAdmin, deleteAdmin } from './support.mjs'
const uid=String(Date.now())
const email=`phase2d-${uid}@example.com`,password='Local-test-2D!942'
createAdmin(email,password)
const browser=await chromium.launch({headless:true}),page=await browser.newPage();const runtime=[];page.on('pageerror',e=>runtime.push(e.message))
let passed=0;const pass=s=>{passed++;console.log('PASS:',s)}
const fx={lesson:'verify-lesson-'+uid,horse:'verify-horse-'+uid,horseName:'Verify avail '+uid,iw:'verify-iw-'+uid,hw:'verify-hw-'+uid}
try{
 await page.goto(`${base}/admin/login`);await page.locator('input[type=email]').fill(email);await page.locator('input[type=password]').fill(password);await page.getByRole('button',{name:'Login',exact:true}).click();await page.waitForURL('**/admin/dashboard',{timeout:15000});pass('real admin authentication')
 await page.goto(`${base}/admin/horses`);await page.getByRole('heading',{name:'Horses',exact:true}).waitFor();pass('admin protected route survives full reload')
 const horseName='Verify horse '+uid
 await page.getByRole('button',{name:'+ Add Horse',exact:true}).click()
 await page.locator('form input[type=text]').nth(0).fill(horseName)
 await page.getByRole('button',{name:'Save',exact:true}).click()
 let row=page.locator('tr').filter({hasText:horseName});await row.waitFor();await page.getByText('Horse created',{exact:true}).waitFor();pass('horse create and notification')
 await row.getByRole('button',{name:'Edit',exact:true}).click();await page.locator('form input[type=text]').nth(1).fill('Test breed');await page.getByRole('button',{name:'Save',exact:true}).click();await row.getByText('Test breed',{exact:true}).waitFor();pass('horse update')
 await row.getByRole('button',{name:'Delete',exact:true}).click();await page.getByRole('heading',{name:'Delete Horse',exact:true}).waitFor();await page.getByRole('button',{name:'Delete',exact:true}).last().click();await row.waitFor({state:'detached'});pass('horse delete and confirmation dialog')
 await page.goto(`${base}/admin/riders`);await page.getByRole('heading',{name:'Riders',exact:true}).waitFor()
 await page.getByRole('button',{name:'+ Add Rider',exact:true}).click();await page.locator('form input[type=text]').nth(0).fill('Verify');await page.locator('form input[type=text]').nth(1).fill(uid);await page.locator('form input[type=email]').fill(email);await page.getByRole('button',{name:'Save',exact:true}).click()
 row=page.locator('tr').filter({hasText:uid});await row.waitFor();pass('rider create')
 await row.getByRole('button',{name:'Edit',exact:true}).click();await page.locator('form input[type=tel]').fill('555-0100');await page.getByRole('button',{name:'Save',exact:true}).click();await row.getByText('555-0100',{exact:true}).waitFor();pass('rider update')
 await row.getByRole('button',{name:'Delete',exact:true}).click();await page.getByRole('heading',{name:'Delete Rider',exact:true}).waitFor();await page.getByRole('button',{name:'Delete',exact:true}).last().click();await row.waitFor({state:'detached'});pass('rider delete')
 // Availability Active toggles persist and drive public slots. Isolated fixture: Sunday 05:00-06:00.
 sql(`INSERT INTO lesson_types(id,name,duration_minutes,price,active) VALUES ('${fx.lesson}','Verify lesson ${uid}',30,1,1);
  INSERT INTO horses(id,name,active) VALUES ('${fx.horse}','${fx.horseName}',1);
  INSERT INTO horse_lesson_types(id,horse_id,lesson_type_id,eligible) VALUES ('verify-e-${uid}','${fx.horse}','${fx.lesson}',1);
  INSERT INTO instructor_availability(id,day_of_week,start_time,end_time,active) VALUES ('${fx.iw}',0,'05:00','06:00',1);
  INSERT INTO horse_recurring_availability(id,horse_id,day_of_week,start_time,end_time,active) VALUES ('${fx.hw}','${fx.horse}',0,'05:00','06:00',1);`)
 const today=new Date(new Date().toLocaleString('en-US',{timeZone:'America/Denver'})),sunday=new Date(today);sunday.setDate(today.getDate()+(7-today.getDay()))
 const sundayDate=`${sunday.getFullYear()}-${String(sunday.getMonth()+1).padStart(2,'0')}-${String(sunday.getDate()).padStart(2,'0')}`
 const publicSlots=async()=>(await slots(fx.lesson,sundayDate)).map(s=>s.start_time)
 const saved=(table,id)=>sql(`SELECT active FROM ${table} WHERE id='${id}'`).trim()==='1'
 const sundayIn=card=>card.locator('div.space-y-3').filter({has:page.getByRole('heading',{name:'Sunday',exact:true})})
 const instructorBox=()=>sundayIn(page.locator('.card').filter({hasText:'Your Weekly Schedule'})).locator('div.rounded').filter({hasText:'05:00:00 – 06:00:00'}).getByRole('checkbox')
 const horseDay=()=>sundayIn(page.locator('.card').filter({hasText:'Horse Availability'}).locator('div.border-b').filter({has:page.getByRole('heading',{name:fx.horseName,exact:true})}))
 const horseBox=()=>horseDay().locator('div.rounded').filter({hasText:'05:00:00 – 06:00:00'}).getByRole('checkbox')
 const warning='Not currently bookable — no instructor hours this day'
 const openAvailability=async()=>{await page.goto(`${base}/admin/availability`);await page.getByRole('heading',{name:'Availability Management',exact:true}).waitFor();await instructorBox().waitFor()}
 await openAvailability()
 await page.getByText('Bookable lesson times are based on both instructor and eligible horse availability.',{exact:true}).waitFor();pass('availability explanation shown')
 assert.deepEqual(await publicSlots(),['05:00','05:30']);assert.equal(await horseDay().getByText(warning).count(),0);pass('active instructor + horse windows create public slots, no warning')
 await instructorBox().uncheck();await page.getByText('Availability window disabled',{exact:true}).waitFor();assert.equal(saved('instructor_availability',fx.iw),false)
 await openAvailability();assert.equal(await instructorBox().isChecked(),false);pass('instructor Active toggle persists after refresh')
 assert.deepEqual(await publicSlots(),[]);pass('inactive instructor window creates no public slots')
 await horseDay().getByText(warning,{exact:true}).waitFor();pass('horse window without instructor hours shows informational warning')
 await instructorBox().check();await page.getByText('Availability window enabled',{exact:true}).waitFor();await openAvailability();assert.equal(await instructorBox().isChecked(),true);assert.equal(await horseDay().getByText(warning).count(),0);assert.deepEqual(await publicSlots(),['05:00','05:30']);pass('instructor re-enable persists and restores slots')
 await horseBox().uncheck();await page.getByText('Availability window disabled',{exact:true}).waitFor();assert.equal(saved('horse_recurring_availability',fx.hw),false)
 await openAvailability();assert.equal(await horseBox().isChecked(),false);pass('horse Active toggle persists after refresh')
 assert.deepEqual(await publicSlots(),[]);pass('inactive horse window does not count toward public slots')
 await horseBox().check();await page.getByText('Availability window enabled',{exact:true}).waitFor();await openAvailability();assert.equal(await horseBox().isChecked(),true);assert.deepEqual(await publicSlots(),['05:00','05:30']);pass('horse re-enable persists and restores slots')
 for(const [path,title] of [['riders','Riders'],['availability','Availability Management'],['bookings','Bookings']]){await page.goto(`${base}/admin/${path}`);await page.getByRole('heading',{name:title,exact:true}).waitFor();pass('admin '+path+' page')}
 assert.deepEqual(runtime,[]);pass('no admin browser exceptions')
 console.log(`Admin browser: ${passed} passed, 0 failed, 0 skipped`)
}finally{
 await browser.close()
 sql(`DELETE FROM horse_recurring_availability WHERE horse_id='${fx.horse}'; DELETE FROM instructor_availability WHERE id='${fx.iw}'; DELETE FROM horse_lesson_types WHERE horse_id='${fx.horse}';
  DELETE FROM horses WHERE id='${fx.horse}' OR name='Verify horse ${uid}'; DELETE FROM lesson_types WHERE id='${fx.lesson}'; DELETE FROM riders WHERE email='${email}';`)
 deleteAdmin(email)
}
