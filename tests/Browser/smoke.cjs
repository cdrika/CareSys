const {chromium}=require(process.env.CARESYS_NODE_MODULES+'/playwright');
const fs=require('fs');
(async()=>{
 const browser=await chromium.launch({headless:true,channel:'msedge'});const results=[];
 const roles=['platform','owner','recruiter','scheduler','supervisor','finance','caregiver','candidate','former','unresolved','owner2','caregiver2'];
 for(const role of roles){
  const context=await browser.newContext({viewport:{width:1440,height:1050}});const page=await context.newPage();const errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://localhost:8080/login');await page.fill('[name=email]',role+'@caresys.test');await page.fill('[name=password]','CareSysDemo!2026');await page.click('button[type=submit]');await page.waitForURL('**/app');
  const paths=await page.locator('nav a').evaluateAll(nodes=>nodes.map(n=>n.getAttribute('href')));
  const workspaces=await page.locator('[data-workspace] option').evaluateAll(nodes=>nodes.map(n=>n.value).filter(x=>x.startsWith('/agency/')));
  if(workspaces.length){await page.goto('http://localhost:8080'+workspaces[0]);paths.push(...await page.locator('nav a').evaluateAll(nodes=>nodes.map(n=>n.getAttribute('href'))));}
  for(const path of [...new Set(paths)]){
   const response=await page.goto('http://localhost:8080'+path);const text=await page.locator('body').innerText();
   if(response.status()!==200||/ErrorException|Whoops!|InvalidArgumentException|Undefined (variable|property)/.test(text))errors.push(path+': HTTP '+response.status());
   const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+3);if(overflow)errors.push(path+': viewport overflow');
  }
  if(role==='owner'){await page.goto('http://localhost:8080'+workspaces[0]);await page.screenshot({path:'build/qa/agency-desktop.png',fullPage:true});}
  if(role==='candidate'){await page.goto('http://localhost:8080/app/privacy');await page.screenshot({path:'build/qa/candidate-privacy.png',fullPage:true});}
  if(role==='caregiver'){await page.setViewportSize({width:390,height:844});await page.goto('http://localhost:8080'+workspaces[0].replace('/dashboard','/today'));await page.screenshot({path:'build/qa/caregiver-mobile.png',fullPage:true});if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+3))errors.push('Mobile viewport overflow');}
  results.push({role,pages:paths.length,errors});console.log(role+': '+paths.length+' pages, '+errors.length+' errors');await context.close();
 }
 await browser.close();fs.writeFileSync('build/qa/browser-results.json',JSON.stringify(results,null,2));
 if(results.some(r=>r.errors.length)){console.log(JSON.stringify(results.filter(r=>r.errors.length)));process.exitCode=1;}
})().catch(e=>{console.error(e.message);process.exitCode=1;});
