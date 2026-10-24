const {chromium}=require(process.env.CARESYS_NODE_MODULES+'/playwright');
(async()=>{
 const browser=await chromium.launch({headless:true,channel:'msedge'});let checked=0;
 for(const role of ['platform','owner','recruiter','scheduler','supervisor','finance','caregiver','candidate','former','unresolved','owner2','caregiver2']){
  const context=await browser.newContext();const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://localhost:8080/login');await page.fill('[name=email]',role+'@caresys.test');await page.fill('[name=password]','CareSysDemo!2026');await page.click('button[type=submit]');await page.waitForURL('**/app');checked++;
  let paths=[];
  if(role==='platform')paths=['/app/platform'];
  if(role==='candidate')paths=['/app/profile','/app/career','/app/privacy','/app/offers'];
  const agency=await page.locator('[data-workspace] option').evaluateAll(nodes=>nodes.map(n=>n.value).find(v=>v.startsWith('/agency/')));
  if(role==='owner')paths=['people','schedule','time','leave','care','finance','settings'].map(x=>agency.replace('/dashboard','/'+x));
  if(role==='caregiver')paths=[agency.replace('/dashboard','/today')];
  for(const path of paths){const response=await page.goto('http://localhost:8080'+path);if(response.status()!==200)throw Error(role+' '+path+' HTTP '+response.status());if(/ErrorException|Whoops!|Undefined (variable|property)/.test(await page.locator('body').innerText()))throw Error(role+' '+path+' rendering error');checked++;}
  if(role==='owner'){
    const me=await context.request.get('http://localhost:8080/api/v1/me/profile');if(me.status()!==200)throw Error('Profile API failed');
    const noCsrf=await context.request.post('http://localhost:8080/actions/agency',{form:{name:'Rejected fixture'}});if(![302,303,403].includes(noCsrf.status())&&noCsrf.status()!==200)throw Error('Unexpected CSRF response');
  }
  if(errors.length)throw Error(errors.join('; '));
  const signout=page.locator('form[action="/logout"] button');if(await signout.count()){await signout.click();await page.waitForURL('**/login');}
  console.log(role+': database-session login, pages and sign-out passed');await context.close();
 }
 await browser.close();console.log('Final check passed: '+checked+' page visits, 12 accounts.');
})().catch(e=>{console.error(e.message);process.exitCode=1;});
