<?php
/**
 * kp-calc-js - ماشین‌حساب JS (فقط صفحه litr-be-kilo)
 */
add_action( 'wp_footer', function () {
    if ( ! is_page( 'litr-be-kilo' ) ) return;
    echo <<<'JS'
<script>
const MIXER_FILL_FACTOR=0.80;
const MIXER_MODELS=[
{cap:50,hp:1,mat:'استیل',url:'/میکسر-پودر-استیل-50-لیتری/'},
{cap:100,hp:2,mat:'استیل',url:'/میکسر-پودر-استیل-100-لیتری/'},
{cap:200,hp:3,mat:'آهن',url:'/میکسر-پودر-آهن-200-لیتری/'},
{cap:200,hp:3,mat:'استیل',url:'/میکسر-پودر-استیل-200-لیتری/'},
{cap:400,hp:5.5,mat:'آهن',url:'/میکسر-پودر-آهن-400-لیتری/'},
{cap:400,hp:5.5,mat:'استیل',url:'/میکسر-پودر-استیل-400-لیتری/'},
{cap:600,hp:7.5,mat:'آهن',url:'/میکسر-پودر-آهن-600-لیتری/'},
{cap:600,hp:7.5,mat:'استیل',url:'/میکسر-پودر-استیل-600-لیتری/'},
{cap:800,hp:10,mat:'آهن',url:'/میکسر-پودر-آهن-800-لیتری/'},
{cap:800,hp:10,mat:'استیل',url:'/میکسر-پودر-استیل-800-لیتری/'},
{cap:1000,hp:15,mat:'آهن',url:'/میکسر-پودر-آهن-1000-لیتری/'},
{cap:1000,hp:10,mat:'استیل',url:'/میکسر-پودر-استیل-1000-لیتری/'},
{cap:1200,hp:20,mat:'استیل',url:'/میکسر-پودر-استیل-1200-لیتری/'},
{cap:1500,hp:20,mat:'آهن',url:'/میکسر-پودر-آهن-1500-لیتری/'},
{cap:1500,hp:15,mat:'استیل',url:'/میکسر-پودر-استیل-1500-لیتری/'},
{cap:1800,hp:25,mat:'آهن',url:'/میکسر-پودر-آهن-1800-لیتری/'},
{cap:1800,hp:25,mat:'استیل',url:'/میکسر-پودر-استیل-1800-لیتری/'},
{cap:2000,hp:30,mat:'آهن',url:'/میکسر-پودر-آهن-2000-لیتری/'}
];
let kpMode="liters_to_kg";
let kpFiltered=[];
let kpDb=[];
const $=id=>document.getElementById(id);
function normalize(t){return (t??'').trim().toLocaleLowerCase('fa-IR').replace(/[يى]/g,'ی').replace(/ك/g,'ک').replace(/\u200c/g,' ').replace(/\s+/g,' ');}
function numFa(v,d=2){return Number.isFinite(v)?new Intl.NumberFormat('fa-IR',{maximumFractionDigits:d,minimumFractionDigits:0}).format(v):'—';}
function notice(msg){const n=$('notice');n.textContent=msg;n.style.display='block';}
function hideNotice(){$('notice').style.display='none';}
async function loadDb(){
  try{
    const r=await fetch('/wp-content/uploads/powder-db.json');
    if(!r.ok)throw new Error('DB fetch failed');
    kpDb=await r.json();
    // مواد جدید مرج (در صورت نبود در فایل)
    const extra=[
      {name:'سیمان',aliases:['cement','سیمان پرتلند'],density:{min:1.10,typical:1.30,max:1.50},category:'chemical'},
      {name:'نسکافه (قهوه فوری)',aliases:['nescafe','instant coffee'],density:{min:0.28,typical:0.33,max:0.38},category:'food'},
      {name:'چسب کاشی پودری',aliases:['tile glue','چسب کاشی'],density:{min:1.20,typical:1.40,max:1.60},category:'chemical'},
      {name:'کود اوره',aliases:['urea','اوره'],density:{min:0.72,typical:0.80,max:0.88},category:'chemical'},
      {name:'کافی میت',aliases:['coffee mate','کافی میت'],density:{min:0.45,typical:0.52,max:0.60},category:'food'},
      {name:'کود NPK',aliases:['npk','کود سه‌گانه'],density:{min:0.90,typical:1.00,max:1.10},category:'chemical'},
      {name:'سولفات پتاسیم (کود)',aliases:['potassium sulfate','سولفات پتاسیم'],density:{min:0.90,typical:1.00,max:1.10},category:'chemical'}
    ];
    extra.forEach(x=>{if(!kpDb.some(i=>i.name===x.name))kpDb.push(x);});
    kpFiltered=[...kpDb];
    renderSelect(kpFiltered);
    loadFromUrl();
  }catch(e){
    console.error(e);
    notice('پایگاه داده مواد بارگذاری نشد. فایل powder-db.json را در wp-content/uploads قرار دهید.');
    renderSelect([]);
  }
}
function renderSelect(items){
  const sel=$('powderType');
  sel.innerHTML='';
  if(!items.length){
    const o=document.createElement('option');o.value='custom';o.textContent='ماده‌ای یافت نشد — چگالی سفارشی';sel.appendChild(o);
    $('customDensityContainer').style.display='block';
    return;
  }
  const groups={food:'مواد غذایی پودری',chemical:'پودرهای شیمیایی و معدنی',animal:'خوراک دام و مواد دامی'};
  for(const[cat,label]of Object.entries(groups)){
    const arr=items.filter(i=>i.category===cat);
    if(!arr.length)continue;
    const g=document.createElement('optgroup');g.label=label;
    arr.forEach(it=>{
      const o=document.createElement('option');
      o.value=it.name;
      o.textContent=it.name+' ('+numFa(it.density.typical,3)+' kg/L)';
      g.appendChild(o);
    });
    sel.appendChild(g);
  }
  const cust=document.createElement('option');cust.value='custom';cust.textContent='سفارشی — ورود چگالی دستی';sel.appendChild(cust);
  if(items.length){sel.selectedIndex=0;$('customDensityContainer').style.display='none';}
  updateCustomVisibility();
}
function updateCustomVisibility(){$('customDensityContainer').style.display=$('powderType').value==='custom'?'block':'none';}
function kpSetMode(m){
  kpMode=m;
  $('tab-l2k').classList.toggle('active',m==='liters_to_kg');
  $('tab-k2l').classList.toggle('active',m==='kg_to_liters');
  $('input-label').textContent=m==='liters_to_kg'?'حجم کاری پودر (لیتر)':'وزن پودر (کیلوگرم)';
  $('capacity').value=m==='liters_to_kg'?'500':'300';
  $('results').style.display='none';
  syncUrl();
}
function kpCalculate(){
  hideNotice();
  const val=parseFloat($('capacity').value);
  if(!Number.isFinite(val)||val<=0){notice('مقدار ورودی باید بیشتر از صفر باشد.');return;}
  const selName=$('powderType').value;
  let dens;
  if(selName==='custom'){
    const c=parseFloat($('customDensity').value);
    if(!Number.isFinite(c)||c<=0){notice('چگالی سفارشی نامعتبر است.');return;}
    dens={min:c,typical:c,max:c};
  }else{
    const item=kpDb.find(i=>i.name===selName);
    if(!item){notice('ماده انتخاب‌شده معتبر نیست.');return;}
    dens=item.density;
    if($('material'))$('material').value=item.name;
  }
  let workVol,typical,minR,maxR;
  if(kpMode==='liters_to_kg'){
    workVol=val;typical=workVol*dens.typical;minR=workVol*dens.min;maxR=workVol*dens.max;
    $('result-label').textContent='وزن تقریبی';
    $('result-main-value').textContent=numFa(typical,2)+' کیلوگرم';
    $('result-input').textContent=numFa(val,2)+' لیتر';
    $('result-min').textContent=numFa(minR,2)+' کیلوگرم';
    $('result-max').textContent=numFa(maxR,2)+' کیلوگرم';
  }else{
    typical=val/dens.typical;minR=val/dens.max;maxR=val/dens.min;workVol=typical;
    $('result-label').textContent='حجم تقریبی';
    $('result-main-value').textContent=numFa(typical,2)+' لیتر';
    $('result-input').textContent=numFa(val,2)+' کیلوگرم';
    $('result-min').textContent=numFa(minR,2)+' لیتر';
    $('result-max').textContent=numFa(maxR,2)+' لیتر';
  }
  const mixerVol=workVol/MIXER_FILL_FACTOR;
  $('result-density').textContent=numFa(dens.typical,3)+' kg/L';
  $('result-working-volume').textContent=numFa(workVol,2)+' L';
  $('result-mixer-volume').textContent=numFa(mixerVol,2)+' L';
  if(dens.min!==dens.max){
    $('range-note').textContent='بازه چگالی: '+numFa(dens.min,3)+' تا '+numFa(dens.max,3)+' kg/L. نتیجه واقعی با تراکم و شرایط ماده تغییر می‌کند.';
  }else{
    $('range-note').textContent='محاسبه بر اساس چگالی واردشده انجام شده است.';
  }
  renderRecommendation(mixerVol);
  $('results').style.display='block';
  trackEvent('calculator_result',{mode:kpMode,material:selName,input:val,density:dens.typical,mixerVol});
  syncUrl();
}
function renderRecommendation(reqVol){
  $('recommendation').style.display='block';
  const maxCap=MIXER_MODELS[MIXER_MODELS.length-1].cap;
  const primary=MIXER_MODELS.find(m=>m.cap>=reqVol)||{cap:null};
  const alts=MIXER_MODELS.filter(m=>m.cap>=reqVol).slice(0,3);
  const mat=$('powderType').value==='custom'?'ماده سفارشی':$('powderType').value;
  if(primary.cap){
    $('recommendation-text').innerHTML='حجم موردنیاز اسمی میکسر حدود <strong>'+numFa(reqVol,0)+' لیتر</strong> است. برای حفظ فضای کاری، ظرفیت پیشنهادی <strong>'+numFa(primary.cap,0)+' لیتر</strong> (موتور '+numFa(primary.hp,1)+' اسب‌بخار، '+primary.mat+') است.';
  }else{
    $('recommendation-text').innerHTML='حجم محاسبه‌شده از ظرفیت‌های تعریف‌شده (تا '+numFa(maxCap,0)+' لیتر) بیشتر است. برای انتخاب دستگاه بزرگ‌تر، اطلاعات فرآیند را برای مشاوره فنی ارسال کنید.';
  }
  $('devices').innerHTML=alts.map(m=>'<div class="device">میکسر پیشنهادی: <strong>'+numFa(m.cap,0)+' لیتر</strong> (موتور '+numFa(m.hp,1)+' اسب‌بخار، '+m.mat+') — <a href="'+m.url+'" target="_blank" rel="noopener">مشاهده محصول ↗</a></div>').join('');
  const msg='سلام، برای انتخاب میکسر پودر مشاوره می‌خواهم.%0A'+
            'ماده: '+encodeURIComponent(mat)+'%0A'+
            'حجم موردنیاز میکسر: '+encodeURIComponent(numFa(reqVol,2))+' لیتر';
  $('whatsappLink').href='https://wa.me/?text='+msg;
  trackEvent('mixer_recommendation',{material:mat,requiredMixerVolume:reqVol,recommendedCapacity:primary.cap});
}
function syncUrl(){
  const p=new URLSearchParams();
  p.set('mode',kpMode);
  const sel=$('powderType').value;
  if(sel!=='custom')p.set('material',sel);
  const v=$('capacity').value;if(v)p.set('value',v);
  history.replaceState(null,'',location.pathname+'?'+p.toString());
}
function loadFromUrl(){
  const p=new URLSearchParams(location.search);
  const m=p.get('mode');if(m==='kg_to_liters'||m==='liters_to_kg')kpSetMode(m);
  const v=p.get('value');if(v&&Number.isFinite(Number(v)))$('capacity').value=v;
  const mat=p.get('material');
  if(mat){
    const match=kpDb.find(i=>normalize(i.name)===normalize(mat));
    if(match){$('powderType').value=match.name;updateCustomVisibility();}
  }
}
document.addEventListener('DOMContentLoaded',()=>{
  if(document.getElementById('powderSearch')){
    document.getElementById('powderSearch').addEventListener('input',e=>{
      const q=normalize(e.target.value);
      kpFiltered=q?kpDb.filter(it=>{
        const hay=normalize([it.name,...(it.aliases||[]),it.category].join(' '));
        return hay.includes(q);
      }):[...kpDb];
      renderSelect(kpFiltered);
    });
  }
  if(document.getElementById('powderType')){
    document.getElementById('powderType').addEventListener('change',()=>{updateCustomVisibility();syncUrl();});
  }
  if(document.getElementById('adviceForm')){
    document.getElementById('adviceForm').addEventListener('submit',e=>{
      e.preventDefault();
      const payload={name:$('name').value.trim(),phone:$('phone').value.trim(),material:$('material').value.trim()||$('powderType').value,message:$('message').value.trim()};
      trackEvent('consultation_form_submit',payload);
      const txt='سلام، درخواست مشاوره انتخاب میکسر دارم.%0A'+
                'نام: '+encodeURIComponent(payload.name)+'%0A'+
                'تماس: '+encodeURIComponent(payload.phone)+'%0A'+
                'ماده: '+encodeURIComponent(payload.material)+'%0A'+
                'توضیحات: '+encodeURIComponent(payload.message);
      window.open('https://wa.me/?text='+txt,'_blank','noopener');
      e.target.reset();
    });
  }
  loadDb();
});
function trackEvent(name,params){
  params=params||{};
  try{
    if(typeof gtag==='function')gtag('event',name,params);
    if(Array.isArray(window.dataLayer))window.dataLayer.push(Object.assign({event:name},params));
  }catch(e){}
}
</script>
JS;
}, 99 );
