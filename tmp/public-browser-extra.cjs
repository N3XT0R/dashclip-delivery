const {chromium}=require('/tmp/dashclip-browser/node_modules/playwright');
(async()=>{
 const browser=await chromium.launch({args:['--no-sandbox']});
 const page=await browser.newPage();
 await page.addInitScript(()=>localStorage.setItem('theme','dark'));
 for(const width of [360,390,768,900,1024,1440,1920]){
  await page.setViewportSize({width,height:900});
  await page.goto('http://web_sharing/',{waitUntil:'networkidle'});
  if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw Error('Dark overflow '+width);
 }
 await page.setViewportSize({width:390,height:844});
 await page.goto('http://web_sharing/',{waitUntil:'networkidle'});
 await page.evaluate(()=>document.documentElement.style.fontSize='200%');
 if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw Error('Text zoom overflow');
 await page.keyboard.press('Tab');
 if(!await page.evaluate(()=>document.activeElement.textContent.includes('Zum Inhalt')))throw Error('Skip link not first');
 await page.keyboard.press('Enter');
 if(await page.evaluate(()=>document.activeElement.id)!=='main-content')throw Error('Skip target not focused');
 console.log(JSON.stringify({darkWidths:7,textEnlargement:'200%',keyboardSkip:true}));
 await browser.close();
})();
