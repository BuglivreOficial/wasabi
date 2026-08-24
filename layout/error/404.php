<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Página não encontrada</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root{
    --ink: #0B1220;
    --ink-2: #101A2E;
    --panel: #131D33;
    --line: rgba(148, 178, 214, 0.14);
    --line-strong: rgba(148, 178, 214, 0.28);
    --text: #EAF0FA;
    --text-dim: #93A3BD;
    --text-faint: #5B6982;
    --teal: #49D9C6;
    --teal-dim: rgba(73, 217, 198, 0.14);
    --coral: #F2705E;
    --radius: 14px;
    --mono: 'JetBrains Mono', monospace;
    --display: 'Space Grotesk', sans-serif;
    --body: 'Inter', sans-serif;
  }

  *{ margin:0; padding:0; box-sizing:border-box; }
  html, body{ height:100%; }

  body{
    font-family: var(--body);
    background:
      radial-gradient(1200px 600px at 15% -10%, rgba(73,217,198,0.09), transparent 60%),
      radial-gradient(900px 500px at 100% 110%, rgba(242,112,94,0.07), transparent 55%),
      var(--ink);
    color: var(--text);
    min-height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 20px;
    position: relative;
    overflow-x: hidden;
  }

  body::before{
    content:"";
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(var(--line) 1px, transparent 1px),
      linear-gradient(90deg, var(--line) 1px, transparent 1px);
    background-size: 42px 42px;
    -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, black 0%, transparent 75%);
    mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, black 0%, transparent 75%);
    opacity: 0.5;
    pointer-events: none;
  }

  .wrap{
    width: 100%;
    max-width: 460px;
    position: relative;
    z-index: 1;
    text-align: center;
  }

  /* ---- Radar / sinal perdido (elemento assinatura) ---- */
  .radar{
    width: 108px;
    height: 108px;
    margin: 0 auto 20px;
    position: relative;
  }

  .radar svg{ width: 100%; height: 100%; overflow: visible; }

  .radar-ring{
    fill: none;
    stroke: var(--line-strong);
    stroke-width: 1.4;
  }

  .radar-ring.mid{ stroke: var(--line); }

  .radar-sweep{
    transform-origin: 54px 54px;
    animation: sweep 3.2s linear infinite;
  }

  .radar-sweep path{
    fill: url(#sweepGradient);
  }

  .radar-dot{
    fill: var(--coral);
    animation: ping 3.2s ease-in-out infinite;
  }

  .radar-center{
    fill: var(--text-faint);
  }

  @keyframes sweep{
    from{ transform: rotate(0deg); }
    to{ transform: rotate(360deg); }
  }

  @keyframes ping{
    0%, 40%{ opacity: 0; transform: scale(0.6); }
    46%{ opacity: 1; transform: scale(1.15); }
    55%{ opacity: 0.9; transform: scale(1); }
    75%, 100%{ opacity: 0; transform: scale(0.6); }
  }

  .eyebrow{
    font-family: var(--mono);
    font-size: 11px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--coral);
    margin-bottom: 14px;
  }

  .code{
    font-family: var(--display);
    font-weight: 700;
    font-size: clamp(64px, 18vw, 96px);
    line-height: 1;
    letter-spacing: -0.02em;
    background: linear-gradient(180deg, var(--text) 0%, var(--text-dim) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 6px;
  }

  h1{
    font-family: var(--display);
    font-weight: 600;
    font-size: 21px;
    letter-spacing: -0.01em;
    margin-bottom: 10px;
  }

  .sub{
    color: var(--text-dim);
    font-size: 14.5px;
    line-height: 1.6;
    max-width: 360px;
    margin: 0 auto 30px;
  }

  .sub code{
    font-family: var(--mono);
    font-size: 13px;
    color: var(--text);
    background: rgba(5, 10, 20, 0.55);
    border: 1px solid var(--line-strong);
    padding: 1px 7px;
    border-radius: 5px;
  }

  .actions{
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
  }

  .btn{
    font-family: var(--display);
    font-weight: 600;
    font-size: 14.5px;
    padding: 13px 24px;
    border-radius: 10px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: filter 0.2s ease, transform 0.15s ease, background 0.2s ease, border-color 0.2s ease;
  }

  .btn:active{ transform: translateY(1px); }

  .btn-primary{
    background: var(--teal);
    color: #06231F;
    border: 1px solid var(--teal);
  }
  .btn-primary:hover{ filter: brightness(1.08); }

  .btn-ghost{
    background: transparent;
    color: var(--text-dim);
    border: 1px solid var(--line-strong);
  }
  .btn-ghost:hover{ color: var(--text); border-color: var(--text-faint); }

  .btn svg{ width: 16px; height: 16px; }

  .foot{
    margin-top: 34px;
    font-family: var(--mono);
    font-size: 11.5px;
    color: var(--text-faint);
    letter-spacing: 0.04em;
  }

  @media (max-width: 400px){
    .radar{ width: 84px; height: 84px; margin-bottom: 16px; }
    h1{ font-size: 19px; }
    .sub{ font-size: 13.5px; margin-bottom: 26px; }
    .actions{ flex-direction: column; }
    .btn{ width: 100%; justify-content: center; }
  }

  @media (max-width: 340px){
    body{ padding: 20px 14px; }
    .code{ font-size: 56px; }
  }

  @media (max-height: 640px){
    body{ align-items: flex-start; padding-top: 28px; }
    .radar{ width: 72px; height: 72px; margin-bottom: 14px; }
    .code{ margin-bottom: 2px; }
    .sub{ margin-bottom: 22px; }
    .foot{ margin-top: 22px; }
  }

  @media (prefers-reduced-motion: reduce){
    .radar-sweep, .radar-dot{ animation: none; }
    *{ transition-duration: 0.001ms !important; }
  }
</style>
</head>
<body>

<div class="wrap">

  <div class="radar">
    <svg viewBox="0 0 108 108" fill="none">
      <defs>
        <linearGradient id="sweepGradient" x1="54" y1="54" x2="54" y2="4" gradientUnits="userSpaceOnUse">
          <stop offset="0" stop-color="var(--teal)" stop-opacity="0.35"/>
          <stop offset="1" stop-color="var(--teal)" stop-opacity="0"/>
        </linearGradient>
      </defs>
      <circle class="radar-ring" cx="54" cy="54" r="50"/>
      <circle class="radar-ring mid" cx="54" cy="54" r="34"/>
      <circle class="radar-ring mid" cx="54" cy="54" r="18"/>
      <g class="radar-sweep">
        <path d="M54 54 L54 4 A50 50 0 0 1 90.3 17.7 Z"/>
      </g>
      <circle class="radar-dot" cx="76" cy="34" r="4"/>
      <circle class="radar-center" cx="54" cy="54" r="3"/>
    </svg>
  </div>

  <p class="eyebrow">Sinal não encontrado</p>
  <div class="code">404</div>
  <h1>Essa página saiu do radar</h1>
  <p class="sub">O endereço <code><?php echo $url ?? 'URL não fornecida'; ?></code> não existe ou foi movido. Confira o link ou volte para um lugar conhecido.</p>


  <p class="foot">ERRO 404 · PÁGINA NÃO ENCONTRADA</p>

</div>

</body>
</html>