import { chromium } from '@playwright/test'
import { createClient } from '@supabase/supabase-js'
import { execFileSync } from 'node:child_process'
import assert from 'node:assert/strict'
const status=JSON.parse(execFileSync('npx',['supabase','status','-o','json'],{encoding:'utf8'}))
const service=createClient(status.API_URL,status.SERVICE_ROLE_KEY,{auth:{persistSession:false}})
const email=`phase2d-${Date.now()}@example.com`,password='Local-test-2D!942'
const {data,error}=await service.auth.admin.createUser({email,password,email_confirm:true});if(error)throw error
const uid=data.user.id
const {error:profileError}=await service.from('admin_profiles').insert({id:uid,auth_user_id:uid,email,is_active:true});if(profileError)throw profileError
const browser=await chromium.launch({headless:true}),page=await browser.newPage();const runtime=[];page.on('pageerror',e=>runtime.push(e.message))
let passed=0;const pass=s=>{passed++;console.log('PASS:',s)}
try{
 await page.goto('http://127.0.0.1:5173/admin/login');await page.locator('input[type=email]').fill(email);await page.locator('input[type=password]').fill(password);await page.getByRole('button',{name:'Login',exact:true}).click();await page.waitForURL('**/admin/dashboard',{timeout:15000});pass('real admin authentication')
 await page.goto('http://127.0.0.1:5173/admin/horses');await page.getByRole('heading',{name:'Horses',exact:true}).waitFor();pass('admin protected route survives full reload')
 const horseName='Verify horse '+uid
 await page.getByRole('button',{name:'+ Add Horse',exact:true}).click()
 await page.locator('form input[type=text]').nth(0).fill(horseName)
 await page.getByRole('button',{name:'Save',exact:true}).click()
 let row=page.locator('tr').filter({hasText:horseName});await row.waitFor();await page.getByText('Horse created',{exact:true}).waitFor();pass('horse create and notification')
 await row.getByRole('button',{name:'Edit',exact:true}).click();await page.locator('form input[type=text]').nth(1).fill('Test breed');await page.getByRole('button',{name:'Save',exact:true}).click();await row.getByText('Test breed',{exact:true}).waitFor();pass('horse update')
 await row.getByRole('button',{name:'Delete',exact:true}).click();await page.getByRole('heading',{name:'Delete Horse',exact:true}).waitFor();await page.getByRole('button',{name:'Delete',exact:true}).last().click();await row.waitFor({state:'detached'});pass('horse delete and confirmation dialog')
 await page.goto('http://127.0.0.1:5173/admin/riders');await page.getByRole('heading',{name:'Riders',exact:true}).waitFor()
 await page.getByRole('button',{name:'+ Add Rider',exact:true}).click();await page.locator('form input[type=text]').nth(0).fill('Verify');await page.locator('form input[type=text]').nth(1).fill(uid);await page.locator('form input[type=email]').fill(email);await page.getByRole('button',{name:'Save',exact:true}).click()
 row=page.locator('tr').filter({hasText:uid});await row.waitFor();pass('rider create')
 await row.getByRole('button',{name:'Edit',exact:true}).click();await page.locator('form input[type=tel]').fill('555-0100');await page.getByRole('button',{name:'Save',exact:true}).click();await row.getByText('555-0100',{exact:true}).waitFor();pass('rider update')
 await row.getByRole('button',{name:'Delete',exact:true}).click();await page.getByRole('heading',{name:'Delete Rider',exact:true}).waitFor();await page.getByRole('button',{name:'Delete',exact:true}).last().click();await row.waitFor({state:'detached'});pass('rider delete')
 for(const [path,title] of [['riders','Riders'],['availability','Availability Management'],['bookings','Bookings']]){await page.goto(`http://127.0.0.1:5173/admin/${path}`);await page.getByRole('heading',{name:title,exact:true}).waitFor();pass('admin '+path+' page')}
 assert.deepEqual(runtime,[]);pass('no admin browser exceptions')
 console.log(`Admin browser: ${passed} passed, 0 failed, 0 skipped`)
}finally{await browser.close();await service.from('horses').delete().eq('name','Verify horse '+uid);await service.from('riders').delete().eq('email',email);await service.from('admin_profiles').delete().eq('id',uid);await service.auth.admin.deleteUser(uid)}
