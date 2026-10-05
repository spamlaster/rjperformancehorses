import { chromium } from '@playwright/test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { execFileSync } from 'node:child_process'
import { createClient } from '@supabase/supabase-js'
const env=Object.fromEntries(readFileSync('.env.local','utf8').split('\n').filter(l=>l.includes('=')).map(l=>{const i=l.indexOf('='); return [l.slice(0,i),l.slice(i+1).trim()]}))
const url=env.VITE_SUPABASE_URL
assert.match(url,/127\.0\.0\.1|localhost/)
const anon=createClient(url,env.VITE_SUPABASE_ANON_KEY,{auth:{persistSession:false}})
const sql=s=>execFileSync('docker',['exec','supabase_db_rjperformancehorses','psql','-U','postgres','-v','ON_ERROR_STOP=1','-Atc',s],{encoding:'utf8'})
const browser=await chromium.launch({headless:true})
const page=await browser.newPage()
const runtime=[];page.on('pageerror',e=>runtime.push(e.message))
let passed=0
const pass=s=>{passed++;console.log('PASS:',s)}
const date='2099-01-06'
async function rpc(lesson='lesson-1') { const {data,error}=await anon.rpc('get_bookable_slots',{p_lesson_type_id:lesson,p_date:date}); assert.equal(error,null); return data }
async function reloadDate(){await page.locator('input[type=date]').fill('');await page.locator('input[type=date]').fill(date);await page.getByRole('button',{name:'2:00 PM',exact:true}).waitFor()}
try {
 const normal=await rpc();assert(normal.some(s=>s.start_time==='14:00'));pass('anonymous HTTP RPC returns real slots')
 for(const table of ['instructor_availability','horse_recurring_availability','blocked_times','bookings','customers','riders','horses']){const {data,error}=await anon.from(table).select('*');assert.equal(error,null);assert.deepEqual(data,[]);pass('anonymous HTTP cannot read '+table)}
 await page.goto('http://127.0.0.1:5173/book')
 await page.getByRole('button',{name:/Walk & Trot/}).click();await page.getByRole('button',{name:'Next',exact:true}).click()
 await page.locator('input[type=date]').fill(date)
 await page.getByRole('button',{name:'2:00 PM',exact:true}).click()
 assert.match(await page.locator('body').innerText(),/Selected: 2:00 PM/);pass('real wizard slots and selected time')
 assert.equal(await page.getByRole('button',{name:'Booking submission coming soon'}).isDisabled(),true)
 await page.locator('input[type=date]').fill('2099-01-08');await page.getByRole('button',{name:'2:00 PM',exact:true}).waitFor();assert(! (await page.locator('body').innerText()).includes('Selected:'));pass('date clears selection')
 await page.getByRole('button',{name:'Back',exact:true}).click();await page.getByRole('button',{name:/Sibling\/Joined/}).click();await page.getByRole('button',{name:'Next',exact:true}).click();await reloadDate();pass('joined lesson with two horses produces slot')
 sql("INSERT INTO blocked_times(id,date,start_time,end_time) VALUES ('verify-instructor','2099-01-06','14:00','15:00')")
 assert(!(await rpc()).some(s=>s.start_time==='14:00'));await page.locator('input[type=date]').fill('');await page.locator('input[type=date]').fill(date);await page.getByRole('button',{name:'3:00 PM',exact:true}).waitFor();assert.equal(await page.getByRole('button',{name:'2:00 PM',exact:true}).count(),0);pass('instructor block removes slot in browser')
 sql("DELETE FROM blocked_times WHERE id='verify-instructor'");await reloadDate();pass('removing instructor block restores slot')
 sql("INSERT INTO bookings(id,customer_id,lesson_type_id,horse_id,start_time,end_time,status) VALUES ('verify-booking','customer-1','lesson-1','horse-1','2099-01-06 14:00','2099-01-06 15:00','confirmed')")
 assert(!(await rpc()).some(s=>s.start_time==='14:00'));pass('booking conflict removes slot')
 sql("UPDATE bookings SET status='cancelled' WHERE id='verify-booking'");assert((await rpc()).some(s=>s.start_time==='14:00'));await reloadDate();pass('cancelled booking restores slot')
 sql("INSERT INTO blocked_times(id,horse_id,date,start_time,end_time) VALUES ('verify-horse','horse-2','2099-01-06','14:00','15:00')")
 assert(!(await rpc('lesson-4')).some(s=>s.start_time==='14:00'));await page.locator('input[type=date]').fill('');await page.locator('input[type=date]').fill(date);await page.getByRole('button',{name:'3:00 PM',exact:true}).waitFor();assert.equal(await page.getByRole('button',{name:'2:00 PM',exact:true}).count(),0);pass('joined lesson with only one available horse disappears')
 sql("DELETE FROM blocked_times WHERE id='verify-horse'");await reloadDate();pass('restoring second horse restores joined slot')
 await page.getByRole('button',{name:'2:00 PM',exact:true}).click();await page.getByRole('button',{name:'Back',exact:true}).click();await page.getByRole('button',{name:/Walk & Trot/}).click();await page.getByRole('button',{name:'Next',exact:true}).click();await page.getByRole('button',{name:'2:00 PM',exact:true}).waitFor();assert(!(await page.locator('body').innerText()).includes('Selected:'));pass('lesson clears selection')
 await page.locator('input[type=date]').fill('2099-01-07');await page.getByText('No available times for this date.',{exact:false}).waitFor();pass('no availability state')
 await page.route('**/rest/v1/rpc/get_bookable_slots',route=>route.fulfill({status:500,contentType:'application/json',body:JSON.stringify({message:'test failure'})}));await page.locator('input[type=date]').fill(date);await page.getByRole('alert').waitFor();pass('RPC error state')
 await page.unroute('**/rest/v1/rpc/get_bookable_slots');await page.getByRole('button',{name:'Try again'}).click();await page.getByRole('button',{name:'2:00 PM',exact:true}).waitFor();pass('retry restores real availability')
 await page.route('**/rest/v1/rpc/get_bookable_slots',async route=>{await new Promise(r=>setTimeout(r,400));await route.continue()});await page.locator('input[type=date]').fill('2099-01-08');await page.getByText('Loading available times…').waitFor();pass('loading state');await page.getByRole('button',{name:'2:00 PM',exact:true}).waitFor();await page.unroute('**/rest/v1/rpc/get_bookable_slots')
 await page.reload();await page.getByRole('button',{name:/Walk & Trot/}).waitFor();pass('public router refresh')
 assert.deepEqual(runtime,[]);pass('no browser runtime exceptions')
 console.log(`Browser/HTTP: ${passed} passed, 0 failed, 0 skipped`)
} finally {
 sql("DELETE FROM blocked_times WHERE id IN ('verify-instructor','verify-horse'); DELETE FROM bookings WHERE id='verify-booking'")
 await browser.close()
}
