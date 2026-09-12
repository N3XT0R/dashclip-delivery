const {chromium} = require('/tmp/dashclip-browser/node_modules/playwright');
const fs = require('node:fs');
(async () => {
 const browser = await chromium.launch({args:['--no-sandbox']});
 const context = await browser.newContext();
 const page = await context.newPage();
 const errors=[], failures=[], checks=[];
 page.on('pageerror',e=>errors.push(e.message));
 page.on('response',r=>{if(r.status()>=400 && !r.url().includes('unknown-public')) failures.push([r.status(),r.url()]);});
 await page.addInitScript(()=>{
   window.metrics={lcp:0,cls:0};
   new PerformanceObserver(l=>{for(const e of l.getEntries()) window.metrics.lcp=e.startTime}).observe({type:'largest-contentful-paint',buffered:true});
   new PerformanceObserver(l=>{for(const e of l.getEntries()) if(!e.hadRecentInput) window.metrics.cls+=e.value}).observe({type:'layout-shift',buffered:true});
 });
 for (const width of [360,390,768,900,1024,1440,1920]) {
   await page.setViewportSize({width,height:1000});
   await page.goto('http://web_sharing/', {waitUntil:'networkidle'});
   await page.locator('.phpdebugbar').evaluateAll(els=>els.forEach(e=>e.style.display='none'));
   const result=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,h1:document.querySelectorAll('h1').length,heroLoaded:document.querySelector('picture img').naturalWidth>0,metrics:window.metrics}));
   if(result.overflow || result.h1!==1 || !result.heroLoaded) throw Error(JSON.stringify({width,...result}));
   checks.push({width,...result});
   if(width===390 || width===1440) await page.screenshot({path:`/var/www/html/tmp/public-${width}.png`,fullPage:true});
 }
 await page.locator('#themeToggle').click();
 await page.reload({waitUntil:'networkidle'});
 if(!await page.locator('html').evaluate(e=>e.classList.contains('dark'))) throw Error('Theme did not persist');
 await page.screenshot({path:'/var/www/html/tmp/public-dark.png',fullPage:true});
 await page.locator('#cookie-accept').click();
 await page.reload({waitUntil:'networkidle'});
 if(await page.locator('#cookie-banner').count()) throw Error('Cookie notice did not stay dismissed');
 await page.setViewportSize({width:390,height:844});
 await page.locator('#mobile-navigation summary').click();
 await page.keyboard.press('Escape');
 if(await page.locator('#mobile-navigation').getAttribute('open')!==null) throw Error('Menu escape failed');
 for (const route of ['impressum','datenschutz','tos','license','changelog','api-docs','game']) {
   const response=await page.goto(`http://web_sharing/${route}`,{waitUntil:'networkidle'});
   const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);
   if(response.status()!==200 || overflow || await page.locator('h1').count()!==1) throw Error(`${route}: status/overflow/heading`);
 }
 await page.locator('#restart').click();
 if(await page.evaluate(()=>document.activeElement.id)!=='gameCanvas') throw Error('Game focus failed');
 await page.keyboard.press('Tab');
 if(await page.evaluate(()=>document.activeElement.id)==='gameCanvas') throw Error('Game keyboard trap');
 for (const route of ['standard/login','standard/register','admin/login']) {
   const response=await page.goto(`http://web_sharing/${route}`,{waitUntil:'networkidle'});
   if(response.status()!==200) throw Error(`Panel ${route}: ${response.status()}`);
   if(await page.locator('link[href*="/public-"]').count()) throw Error('Public CSS loaded on panel');
 }
 const noJs=await browser.newContext({javaScriptEnabled:false,viewport:{width:390,height:844}});
 const plain=await noJs.newPage();
 await plain.goto('http://web_sharing/');
 await plain.locator('#mobile-navigation summary').click();
 if(!await plain.locator('#mobile-navigation a').first().isVisible()) throw Error('No JS menu failed');
 const blocked=await browser.newContext();
 const blockedPage=await blocked.newPage();
 await blockedPage.addInitScript(()=>Object.defineProperty(window,'localStorage',{get(){throw Error('Storage denied')}}));
 await blockedPage.goto('http://web_sharing/',{waitUntil:'networkidle'});
 await blockedPage.locator('#themeToggle').click();
 await blockedPage.locator('#cookie-accept').click();
 if(await blockedPage.locator('#cookie-banner').count()) throw Error('Blocked storage broke cookies');
 const result={checks,errors,failures,themePersistence:true,cookiePersistence:true,noJsNavigation:true,blockedStorage:true,gameKeyboard:true,panelAuth:true};
 fs.writeFileSync('/var/www/html/tmp/public-browser-results.json',JSON.stringify(result,null,2));
 console.log(JSON.stringify(result));
 await browser.close();
})();
