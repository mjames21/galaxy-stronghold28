<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>StrongHold28 — Election Data Platform</title>
  <meta name="description" content="Stronghold 28: forecasting, seat projection, GOTV planning, and PVT for modern campaigns." />
  <!-- Tailwind (CDN). If you compile Tailwind already, remove this and use your built CSS. -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-white text-gray-900">
  <!-- Header -->
  <header class="border-b bg-white/90 backdrop-blur sticky top-0 z-40">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 h-16 flex items-center justify-between">
      <a href="#" class="font-semibold tracking-tight">Stronghold 28</a>
      <nav class="hidden md:flex items-center gap-6 text-sm">
        <a href="#why" class="hover:underline">Why Stronghold</a>
        <a href="#modules" class="hover:underline">Modules</a>
        <a href="#how" class="hover:underline">How it works</a>
        <a href="#methodology" class="hover:underline">Methodology</a>
        <a href="#credibility" class="hover:underline">Credibility</a>
        <a href="#faq" class="hover:underline">FAQ</a>
      </nav>
      <div class="flex items-center gap-2">
        <a href="#modules" class="px-4 py-2 rounded border text-sm hover:bg-gray-50">Explore</a>
        <a href="/login" class="px-4 py-2 rounded bg-gray-900 text-white hover:bg-black text-sm">Login</a>
      </div>
    </div>
  </header>

  <!-- Hero -->
  <section class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-gray-50 to-white"></div>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 relative py-20">
      <div class="max-w-3xl">
        <h1 class="text-4xl md:text-5xl font-semibold leading-tight">
          The election decision OS for modern campaigns.
        </h1>
        <p class="mt-4 text-lg text-gray-600">
          Stronghold 28 unifies <span class="font-medium">data science, behavioral science, and political science</span>
          into one toolkit—forecast outcomes, allocate seats, plan GOTV, and verify results in real time.
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
          <a href="#modules" class="px-5 py-3 rounded bg-gray-900 text-white hover:bg-black text-sm">Explore Modules</a>
          <a href="#how" class="px-5 py-3 rounded border text-sm hover:bg-gray-50">How it works</a>
        </div>
        <div class="mt-4 text-xs text-gray-500">
          Anonymous Edition: built for training and internal evaluation.
        </div>
      </div>
    </div>
  </section>

  <!-- Why Stronghold -->
  <section id="why" class="mx-auto max-w-7xl px-4 sm:px-6 py-14">
    <div class="max-w-3xl">
      <h2 class="text-2xl font-semibold">Why Stronghold 28</h2>
      <p class="mt-3 text-gray-600">
        Campaigns fail not for lack of passion—but for lack of quantified certainty. Stronghold 28 turns uncertainty into
        a planning advantage by simulating thousands of plausible elections, revealing
        <span class="font-medium">where to fight, what to expect, and how to win</span>.
      </p>
    </div>
    <div class="mt-8 grid md:grid-cols-3 gap-6">
      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">Forecast with confidence</h3>
        <p class="mt-2 text-sm text-gray-600">Dirichlet–Beta Monte Carlo quantifies vote shares, turnout, and uncertainty—down to each district.</p>
      </div>
      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">Allocate effort with precision</h3>
        <p class="mt-2 text-sm text-gray-600">Seat Projection translates votes into seats. See marginal paths, coalition math, and tipping points.</p>
      </div>
      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">Act and verify in real time</h3>
        <p class="mt-2 text-sm text-gray-600">GOTV planning and PVT verification keep strategy and truth aligned on election day.</p>
      </div>
    </div>
  </section>

  <!-- Modules -->
  <section id="modules" class="bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-14">
      <h2 class="text-2xl font-semibold">Modules</h2>
      <p class="mt-2 text-gray-600">Everything ties together, end-to-end.</p>

      <div class="mt-8 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="p-6 border rounded bg-white">
          <h3 class="font-semibold">Forecast Engine</h3>
          <p class="mt-2 text-sm text-gray-600">Dirichlet–Beta Monte Carlo forecasts vote share and uncertainty per district.</p>
          <a href="/login" class="mt-3 inline-block text-sm text-gray-900 underline">Login</a>
        </div>
        <div class="p-6 border rounded bg-white">
          <h3 class="font-semibold">Seat Projection</h3>
          <p class="mt-2 text-sm text-gray-600">Convert votes to seats (FPTP/PR). See win paths and seat probabilities.</p>
          <a href="/login" class="mt-3 inline-block text-sm text-gray-900 underline">Login</a>
        </div>
        <div class="p-6 border rounded bg-white">
          <h3 class="font-semibold">GOTV Lab</h3>
          <p class="mt-2 text-sm text-gray-600">Run uplift experiments, measure field impact, prioritise mobilization.</p>
          <a href="/login" class="mt-3 inline-block text-sm text-gray-900 underline">Login</a>
        </div>
        <div class="p-6 border rounded bg-white">
          <h3 class="font-semibold">Scenario Lab</h3>
          <p class="mt-2 text-sm text-gray-600">Stress test assumptions: turnout shocks, regional swings, alliances.</p>
          <a href="/login" class="mt-3 inline-block text-sm text-gray-900 underline">Login</a>
        </div>
        <div class="p-6 border rounded bg-white">
          <h3 class="font-semibold">PVT Verifier</h3>
          <p class="mt-2 text-sm text-gray-600">Parallel Vote Tabulation with quality gates and discrepancy flags.</p>
          <a href="/login" class="mt-3 inline-block text-sm text-gray-900 underline">Login</a>
        </div>
        <div class="p-6 border rounded bg-white">
          <h3 class="font-semibold">Win-Probability Map</h3>
          <p class="mt-2 text-sm text-gray-600">Geospatial layer of win odds and uncertainty bands.</p>
          <span class="mt-3 inline-block text-xs text-gray-500">Coming soon</span>
        </div>
      </div>
    </div>
  </section>

  <!-- How it Works -->
  <section id="how" class="mx-auto max-w-7xl px-4 sm:px-6 py-14">
    <h2 class="text-2xl font-semibold">How it works</h2>
    <div class="mt-6 grid md:grid-cols-3 gap-6">
      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">1) Ingest</h3>
        <p class="mt-2 text-sm text-gray-600">Import past results, registry snapshots, field samples, and polls.</p>
      </div>
      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">2) Simulate</h3>
        <p class="mt-2 text-sm text-gray-600">Dirichlet for shares, Beta for turnout — thousands of Monte Carlo runs.</p>
      </div>
      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">3) Allocate & Act</h3>
        <p class="mt-2 text-sm text-gray-600">Translate votes to seats, target GOTV, monitor results with PVT.</p>
      </div>
    </div>
  </section>

  <!-- Methodology -->
  <section id="methodology" class="bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-14">
      <h2 class="text-2xl font-semibold">Methodology</h2>
      <div class="mt-6 grid md:grid-cols-3 gap-6">
        <div class="p-6 border rounded bg-white">
          <h3 class="font-semibold">Dirichlet–Beta Monte Carlo</h3>
          <p class="mt-2 text-sm text-gray-600">Vote shares (sum to 100%) and turnout (0–1) modeled correctly, then simulated many times.</p>
        </div>
        <div class="p-6 border rounded bg-white">
          <h3 class="font-semibold">Bayesian Updating</h3>
          <p class="mt-2 text-sm text-gray-600">New data refines the priors; forecasts learn over time.</p>
        </div>
        <div class="p-6 border rounded bg-white">
          <h3 class="font-semibold">Verification & Audit</h3>
          <p class="mt-2 text-sm text-gray-600">Parallel tabulation, anomaly detection, and transparent change logs.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Credibility -->
  <section id="credibility" class="mx-auto max-w-7xl px-4 sm:px-6 py-14">
    <h2 class="text-2xl font-semibold">Credibility: who uses these methods</h2>
    <p class="mt-2 text-gray-600">
      StrongHold28 follows the same family of <span class="font-medium">Bayesian Monte Carlo</span> and
      <span class="font-medium">MRP (multilevel regression &amp; post-stratification)</span> techniques used by
      recognized election forecasters and peer-reviewed research.
    </p>

    <div class="mt-8 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">United States</h3>
        <ul class="mt-2 text-sm text-gray-600 list-disc list-inside space-y-1">
          <li>FiveThirtyEight: Bayesian polling blends &amp; Monte Carlo paths.</li>
          <li>The Economist: Bayesian hierarchical presidential model (MCMC).</li>
          <li>Drew Linzer (JASA): dynamic Bayesian state-level forecasting.</li>
        </ul>
      </div>

      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">United Kingdom</h3>
        <ul class="mt-2 text-sm text-gray-600 list-disc list-inside space-y-1">
          <li>YouGov MRP: constituency-level estimates using Bayesian MRP.</li>
          <li>Widely covered in 2017–2019 &amp; later UK general elections.</li>
        </ul>
      </div>

      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">Canada</h3>
        <ul class="mt-2 text-sm text-gray-600 list-disc list-inside space-y-1">
          <li>338Canada: probabilistic projections combining polls &amp; history.</li>
        </ul>
      </div>

      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">New Zealand</h3>
        <ul class="mt-2 text-sm text-gray-600 list-disc list-inside space-y-1">
          <li>Bayesian state-space models (e.g., Poll-of-Polls, Stan/R).</li>
        </ul>
      </div>

      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">Europe (multi-party systems)</h3>
        <ul class="mt-2 text-sm text-gray-600 list-disc list-inside space-y-1">
          <li>Dynamic Bayesian &amp; coalition probability modeling in academia and industry.</li>
        </ul>
      </div>

      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">Methodology references</h3>
        <ul class="mt-2 text-sm text-gray-600 list-disc list-inside space-y-1">
          <li>Dirichlet–Multinomial &amp; Beta–Binomial conjugacy (Bayesian updating).</li>
          <li>MRP &amp; Bayesian hierarchical models (Gelman et&nbsp;al.).</li>
          <li>Dynamic Bayesian election forecasting (Linzer; HDSR/Gelman).</li>
        </ul>
      </div>
    </div>

    <div class="mt-8 p-4 border rounded bg-gray-50 text-sm text-gray-700">
      <p class="mb-2"><span class="font-medium">How Stronghold 28 applies this:</span></p>
      <ul class="list-disc list-inside space-y-1">
        <li><span class="font-medium">Dirichlet</span> for party vote shares (sum to 100%).</li>
        <li><span class="font-medium">Beta</span> for turnout (bounded 0–1).</li>
        <li><span class="font-medium">Monte Carlo</span> simulations for national/district probabilities.</li>
        <li><span class="font-medium">Bayesian updating</span> as new polls and field data arrive.</li>
      </ul>
    </div>
  </section>

  <!-- FAQ -->
  <section id="faq" class="mx-auto max-w-7xl px-4 sm:px-6 py-14">
    <h2 class="text-2xl font-semibold">FAQ</h2>
    <div class="mt-6 grid md:grid-cols-2 gap-6">
      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">Is this a predictor?</h3>
        <p class="mt-2 text-sm text-gray-600">It’s a probability model. We quantify uncertainty and highlight where strategy changes outcomes.</p>
      </div>
      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">What data do we need?</h3>
        <p class="mt-2 text-sm text-gray-600">Past results, registry/turnout history, and any polling or field samples you trust.</p>
      </div>
      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">Does it handle coalitions?</h3>
        <p class="mt-2 text-sm text-gray-600">Yes—Scenario Lab lets you define joint lists or shared candidates and re-run the sims.</p>
      </div>
      <div class="p-6 border rounded bg-white">
        <h3 class="font-semibold">How do we verify results?</h3>
        <p class="mt-2 text-sm text-gray-600">The PVT module collects station-level tallies and flags discrepancies against expected ranges.</p>
      </div>
    </div>

    <div class="mt-8 flex flex-wrap gap-3">
      <a href="#modules" class="px-5 py-3 rounded border text-sm hover:bg-gray-50">Explore Modules</a>
      <a href="/login" class="px-5 py-3 rounded bg-gray-900 text-white hover:bg-black text-sm">Login</a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="border-t mt-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-8 text-sm text-gray-600 flex flex-col md:flex-row items-center justify-between gap-3">
      <div>© <span id="year"></span> Stronghold 28. All rights reserved.</div>
      <div class="flex items-center gap-4">
        <a href="#privacy" class="hover:underline">Privacy</a>
        <a href="#terms" class="hover:underline">Terms</a>
        <a href="#contact" class="hover:underline">Contact</a>
      </div>
    </div>
  </footer>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>
</body>
</html>
