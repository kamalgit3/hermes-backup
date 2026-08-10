<?php
/**
 * Shortcode [kp_calculator] - CSS + HTML (JS جداگانه: kp-calc-js)
 */
add_shortcode( 'kp_calculator', function () {
    $css = <<<'CSS'
<style>
.kp-calc *{box-sizing:border-box}
.kp-calc{max-width:820px;margin:2rem auto;padding:1.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 8px 28px rgba(15,23,42,.07);font-family:Tahoma,Arial,sans-serif;line-height:1.6;color:#1e293b;direction:rtl}
.kp-calc h1{text-align:center;color:#075fba;margin:0 0 1.5rem;font-size:1.6rem}
.kp-calc .tabs{display:grid;grid-template-columns:1fr 1fr;border:1px solid #cbd5e1;border-radius:10px;overflow:hidden;margin-bottom:1.2rem}
.kp-calc .tab-btn{min-height:48px;border:0;background:#f8fafc;color:#334155;font-weight:700;cursor:pointer;transition:.15s}
.kp-calc .tab-btn.active{background:#0879e8;color:#fff}
.kp-calc label{display:block;font-weight:700;margin:1rem 0 .4rem;font-size:.95rem}
.kp-calc input[type="number"],.kp-calc input[type="search"]{width:100%;min-height:46px;padding:.6rem .8rem;border:1px solid #cbd5e1;border-radius:9px;font:inherit}
.kp-calc input:focus{outline:none;border-color:#0879e8;box-shadow:0 0 0 3px rgba(8,121,232,.12)}
.kp-calc .search-hint{font-size:.75rem;color:#64748b;margin-top:.3rem}
.kp-calc .select-wrap{border:1px solid #cbd5e1;border-radius:10px;max-height:260px;overflow-y:auto;background:#fff}
.kp-calc #powderType{width:100%;border:0;border-radius:0;min-height:200px;padding:.4rem;font:inherit}
.kp-calc #powderType:focus{outline:none}
.kp-calc .primary-btn{width:100%;min-height:56px;font-size:1.05rem;font-weight:800;background:#16a34a;color:#fff;border:0;border-radius:10px;cursor:pointer;margin-top:1.2rem}
.kp-calc .primary-btn:hover{background:#15803d}
.kp-calc .results{display:none;margin-top:1.5rem;padding:1.2rem;border:1px solid #cfe0f1;border-radius:12px;background:#f8fbff}
.kp-calc .result-main{text-align:center;padding:1rem;background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:1rem}
.kp-calc .result-main strong{display:block;color:#16a34a;font-size:1.7rem}
.kp-calc .grid{display:grid;grid-template-columns:repeat(2,1fr);gap:.6rem}
.kp-calc .item{background:#fff;border:1px solid #e2e8f0;border-radius:9px;padding:.7rem;text-align:center}
.kp-calc .item span{display:block;font-size:.75rem;color:#64748b;margin-bottom:.2rem}
.kp-calc .item strong{font-size:1rem}
.kp-calc .range-note{margin-top:.8rem;font-size:.8rem;color:#475569;background:#fff;border-radius:9px;padding:.6rem}
.kp-calc .recommendation{display:none;margin-top:1rem;padding:1rem;border:2px solid #fbbf24;border-radius:12px;background:#fffbeb}
.kp-calc .recommendation strong{color:#92400e}
.kp-calc .device{margin-top:.6rem;padding:.6rem;border:1px solid #fde68a;border-radius:8px;background:#fff}
.kp-calc .actions{display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-top:.8rem}
.kp-calc .actions a,.kp-calc .actions button{min-height:48px;border-radius:8px;display:flex;align-items:center;justify-content:center;text-decoration:none;font-weight:700;cursor:pointer}
.kp-calc .whatsapp-btn{background:#128c7e;color:#fff}
.kp-calc .secondary-btn{background:#eef6ff;color:#075fba;border:1px solid #bfdbfe}
.kp-calc .advice{margin-top:2rem;padding:1.2rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px}
.kp-calc .advice form{display:grid;gap:.8rem}
.kp-calc .advice textarea{width:100%;min-height:100px;resize:vertical;border:1px solid #cbd5e1;border-radius:9px;padding:.6rem;font:inherit}
.kp-calc .form-row{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}
@media(max-width:600px){.kp-calc{padding:1rem;margin:1rem;border-radius:12px}.kp-calc h1{font-size:1.3rem}.kp-calc .tab-btn{font-size:.85rem;padding:.6rem}.kp-calc .grid,.kp-calc .form-row,.kp-calc .actions{grid-template-columns:1fr}.kp-calc .primary-btn{min-height:52px;font-size:1rem}}
</style>
CSS;

    $html = <<<'HTML'
<div class="kp-calc">
<h1 id="calculator-title">ماشین حساب هوشمند تبدیل لیتر به کیلوگرم پودر و برعکس</h1>
<div class="tabs" role="tablist" aria-label="نوع تبدیل">
<button type="button" id="tab-l2k" class="tab-btn active" onclick="kpSetMode('liters_to_kg')">لیتر ← کیلوگرم</button>
<button type="button" id="tab-k2l" class="tab-btn" onclick="kpSetMode('kg_to_liters')">کیلوگرم ← لیتر</button>
</div>
<label id="input-label" for="capacity">حجم کاری پودر (لیتر)</label>
<input id="capacity" type="number" min="0.001" step="any" value="500" inputmode="decimal">
<label for="powderSearch">جستجوی ماده</label>
<div class="search-wrap">
<input id="powderSearch" type="search" placeholder="نام فارسی، انگلیسی یا فرمول شیمیایی؛ مثلاً NaCl یا نمک">
<div class="search-hint">جستجو بر اساس نام و نام‌های جایگزین ماده انجام می‌شود.</div>
</div>
<label for="powderType">انتخاب ماده و چگالی حجمی</label>
<div class="select-wrap">
<select id="powderType" size="12" aria-label="انتخاب ماده پودری"></select>
</div>
<div id="customDensityContainer" class="custom-density" style="display:none;margin-top:.8rem;padding:.8rem;border:1px dashed #cbd5e1;background:#f8fafc;border-radius:10px">
<label for="customDensity">چگالی حجمی سفارشی (kg/L)</label>
<input id="customDensity" type="number" min="0.0001" step="0.0001" value="0.60" inputmode="decimal">
</div>
<div id="notice" class="notice" role="alert" style="display:none;margin-top:.6rem;padding:.6rem;border-radius:8px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;font-size:.85rem"></div>
<button type="button" class="primary-btn" onclick="kpCalculate()">محاسبه</button>
<section id="results" class="results" aria-live="polite">
<div class="result-main">
<span id="result-label">وزن تقریبی</span>
<strong id="result-main-value">—</strong>
</div>
<div class="grid">
<div class="item"><span>مقدار ورودی</span><strong id="result-input">—</strong></div>
<div class="item"><span>چگالی معمول</span><strong id="result-density">—</strong></div>
<div class="item"><span>حداقل نتیجه</span><strong id="result-min">—</strong></div>
<div class="item"><span>حداکثر نتیجه</span><strong id="result-max">—</strong></div>
<div class="item"><span>حجم کاری</span><strong id="result-working-volume">—</strong></div>
<div class="item"><span>حجم موردنیاز میکسر</span><strong id="result-mixer-volume">—</strong></div>
</div>
<div id="range-note" class="range-note"></div>
<div id="recommendation" class="recommendation">
<h3>پیشنهاد ظرفیت میکسر</h3>
<div id="recommendation-text"></div>
<div id="devices"></div>
<div class="actions">
<a id="whatsappLink" class="whatsapp-btn" href="#" target="_blank" rel="noopener">مشاوره در واتساپ</a>
<button type="button" class="secondary-btn" onclick="document.getElementById('advice').scrollIntoView({behavior:'smooth'})">درخواست مشاوره فنی</button>
</div>
</div>
</section>
<p style="margin-top:1.2rem;color:#64748b;font-size:.75rem;text-align:center">ضریب پرشدگی پیش‌فرض برای برآورد ظرفیت اسمی میکسر: ۸۰٪. انتخاب نهایی دستگاه باید بر اساس مشخصات واقعی ماده و فرآیند انجام شود.</p>
</div>
<section id="advice" class="kp-calc advice">
<h2>فرم مشاوره انتخاب میکسر</h2>
<form id="adviceForm">
<div class="form-row">
<input id="name" name="name" type="text" placeholder="نام و نام خانوادگی" required>
<input id="phone" name="phone" type="tel" placeholder="شماره تماس" required>
</div>
<input id="material" name="material" type="text" placeholder="نام ماده">
<textarea id="message" name="message" placeholder="حجم یا وزن بچ، نوع ماده و توضیحات فنی"></textarea>
<button type="submit" class="primary-btn">ارسال درخواست مشاوره</button>
</form>
</section>
HTML;

    return $css . $html;
});
