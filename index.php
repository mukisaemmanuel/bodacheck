<?php

require_once __DIR__ . '/includes/header.php';
$base = baseUrl();
?>

<!-- ============================================================ -->
<!-- HERO SECTION                                                  -->
<!-- ============================================================ -->
<div class="hero">
    <h1 class="hero-title">Digital Safety ID for <span>Boda Boda</span> Riders</h1>
    <p class="hero-subtitle">
        A lightweight compliance and safety ID system for Uganda's 2 million+ boda boda riders.
        Built on QR codes and mobile money integration. No app needed for passengers.
    </p>
    <div class="hero-buttons">
        <a href="<?php echo $base; ?>/register.php" class="btn btn-primary">Register as a Rider</a>
        <a href="<?php echo $base; ?>/login.php" class="btn btn-secondary">Login</a>
    </div>
</div>

<!-- ============================================================ -->
<!-- THE SCALE OF THE PROBLEM                                      -->
<!-- ============================================================ -->
<div class="problem-section">
    <h2 class="section-title">The <span>Scale</span> of the Problem</h2>

    <div class="stats-row">
        <div class="stat-highlight">
            <div class="stat-highlight-number">5,383</div>
            <div class="stat-highlight-label">Road deaths in Uganda, 2025</div>
        </div>
        <div class="stat-highlight">
            <div class="stat-highlight-number">47%</div>
            <div class="stat-highlight-label">Of all road fatalities are motorcyclists &amp; passengers</div>
        </div>
        <div class="stat-highlight">
            <div class="stat-highlight-number">94%</div>
            <div class="stat-highlight-label">Of Kampala boda riders have no driving licence</div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ROOT CAUSES                                                   -->
<!-- ============================================================ -->
<div class="problem-section">
    <h2 class="section-title">Why This <span>Happens</span></h2>

    <div class="causes-grid">
        <div class="cause-card">
            <h3>No Barrier to Entry</h3>
            <p>98% of boda riders have no Public Service Vehicle permit. 73% have no logbook. Anyone buys or rents a bike and starts riding tomorrow.</p>
        </div>
        <div class="cause-card">
            <h3>No Fast Verification</h3>
            <p>Traffic police have no quick way to verify a rider's licence, helmet status, or crash history on the roadside. Paper checks are slow, bribeable, and easily faked.</p>
        </div>
        <div class="cause-card">
            <h3>No Passenger Visibility</h3>
            <p>Passengers have zero way to know if their rider is trained, insured, or has a dangerous crash history before they hop on.</p>
        </div>
        <div class="cause-card">
            <h3>Registration Can't Keep Up</h3>
            <p>25,000 new motorcycles enter Kampala every month but KCCA's ability to register and track them is nowhere near that pace.</p>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- THE SOLUTION — HOW BODACHECK WORKS                            -->
<!-- ============================================================ -->
<div class="solution-section">
    <h2 class="section-title">How <span>BodaCheck</span> Works</h2>
    <p style="text-align:center; color:var(--grey-light); max-width:600px; margin:0 auto var(--sp-6); line-height:1.6;">
        The QR links to a simple web page — no app needed for passengers — showing the rider's name,
        licence status, SACCO membership, crash history score, and whether they are currently insured.
        Riders with clean scores get a green badge. Bad actors get flagged red.
        A clean score means lower insurance premiums via Mobile Money integration.
    </p>

    <div class="flow-steps">
        <div class="flow-step">
            <div class="flow-step-icon">1</div>
            <div class="flow-step-title">Rider Registers</div>
            <div class="flow-step-desc">Phone + bike + ID + MoMo payment</div>
        </div>
        <div class="flow-arrow">&rarr;</div>
        <div class="flow-step">
            <div class="flow-step-icon">2</div>
            <div class="flow-step-title">Gets QR Sticker</div>
            <div class="flow-step-desc">On helmet &amp; bike</div>
        </div>
        <div class="flow-arrow">&rarr;</div>
        <div class="flow-step">
            <div class="flow-step-icon">3</div>
            <div class="flow-step-title">Passenger Scans</div>
            <div class="flow-step-desc">Sees rider profile in browser</div>
        </div>
        <div class="flow-arrow">&rarr;</div>
        <div class="flow-step">
            <div class="flow-step-icon">4</div>
            <div class="flow-step-title">Police Scan</div>
            <div class="flow-step-desc">Instant verification at stops</div>
        </div>
        <div class="flow-arrow">&rarr;</div>
        <div class="flow-step">
            <div class="flow-step-icon">5</div>
            <div class="flow-step-title">Violations Logged</div>
            <div class="flow-step-desc">Score updates live</div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- WHY IT'S BETTER — COMPARISON                                  -->
<!-- ============================================================ -->
<div class="problem-section">
    <h2 class="section-title">Why It's <span>Better</span></h2>

    <div class="comparison-grid">
        <div class="comparison-card">
            <div class="vs-label">vs. Paper licensing drives</div>
            <h3>Unfakeable Records</h3>
            <p>Paper licences are forged in hours. A QR-linked digital record can't be faked — it's tied to a phone number and national ID.</p>
        </div>
        <div class="comparison-card">
            <div class="vs-label">vs. SafeBoda's approach</div>
            <h3>Covers Every Rider</h3>
            <p>SafeBoda only covers riders on their platform. BodaCheck covers every independent rider — the 98% SafeBoda doesn't touch.</p>
        </div>
        <div class="comparison-card">
            <div class="vs-label">vs. Police roadblocks</div>
            <h3>Digital Accountability</h3>
            <p>Roadblocks are bribes in disguise. A QR scan takes 3 seconds and logs the check digitally — no cash changes hands.</p>
        </div>
        <div class="comparison-card">
            <div class="vs-label">vs. Awareness campaigns</div>
            <h3>Permanent Records</h3>
            <p>Campaigns change nothing without accountability. BodaCheck creates a permanent, visible safety record that follows the rider.</p>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- REVENUE MODEL                                                 -->
<!-- ============================================================ -->
<div class="revenue-section">
    <h2 class="section-title">Revenue <span>Model</span></h2>
    <p style="text-align:center; color:var(--grey-light); max-width:600px; margin:0 auto var(--sp-6); line-height:1.6;">
        Three revenue streams: rider subscriptions, insurance referral fees, and government licensing.
    </p>

    <div class="revenue-streams">
        <div class="revenue-card">
            <h3>Rider Subscriptions</h3>
            <p>Annual registration fee of UGX 10,000–15,000/year — cheap enough to be accessible to every rider.</p>
        </div>
        <div class="revenue-card">
            <h3>Insurance Integration</h3>
            <p>Riders with clean scores get cheaper insurance premiums. We earn referral fees from insurance partners.</p>
        </div>
        <div class="revenue-card">
            <h3>B2G Licensing</h3>
            <p>Sell the dashboard and analytics to KCCA and Uganda Police as their official registry tool.</p>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- FAQ — ANTICIPATED QUESTIONS                                   -->
<!-- ============================================================ -->
<div class="faq-section">
    <h2 class="section-title">Common <span>Questions</span></h2>

    <div class="faq-item">
        <div class="faq-question">How do you get boda riders to actually register? They'll resist anything that feels like government surveillance.</div>
        <div class="faq-answer">The incentive is financial, not regulatory. Registered riders get access to lower-cost insurance, preferential treatment at SACCO loan schemes, and a visible "green badge" that builds passenger trust and means more rides. You pull people in with benefits before you push with enforcement. Launch through existing SACCOs — they already have rider networks and trust.</div>
    </div>

    <div class="faq-item">
        <div class="faq-question">A passenger won't scan a QR before getting on a boda — that's not how it works in Kampala.</div>
        <div class="faq-answer">You're right that it won't happen spontaneously. So we don't rely on it as the primary safety mechanism. The QR is mostly for police and SACCO officials. For passengers, we build a simple 2-second UX: scan the sticker on the helmet and you see green or red in the browser — no app, no login. Over time, as green-badge riders get more rides, the market selects for compliance.</div>
    </div>

    <div class="faq-item">
        <div class="faq-question">Couldn't KCCA or Uganda Police just build this themselves?</div>
        <div class="faq-answer">They've been trying to for years and failing. Uganda's GovTech index improved from 0.639 to 0.858 between 2020 and 2022, but execution on ground-level systems is still slow. We build it, prove it works with 10,000 riders, then license it to them. That's exactly how iRembo worked in Rwanda — private build, government adoption.</div>
    </div>

    <div class="faq-item">
        <div class="faq-question">What stops someone from buying a fake QR sticker?</div>
        <div class="faq-answer">Each QR is cryptographically signed and links to a live database. Scanning a fake sticker returns an "unregistered" result — the same as red. The sticker itself has no value without the live record behind it. We also watermark stickers with a serial number tied to the rider's phone.</div>
    </div>

    <div class="faq-item">
        <div class="faq-question">Is this not just a surveillance tool the government can misuse?</div>
        <div class="faq-answer">The data we collect is minimal: phone number, national ID, bike registration, and crash log. No GPS tracking. No real-time location. It's closer to a driver's licence record than a surveillance system. We publish a clear data policy and store nothing the government doesn't already require on paper — we're just digitising what exists.</div>
    </div>

    <div class="faq-item">
        <div class="faq-question">What's your go-to-market — how do you get to scale?</div>
        <div class="faq-answer">Start in one Kampala division — Makindye or Kawempe — with one SACCO partner. Onboard 500 riders in month one at zero cost. Show that green-badge riders earn 15–20% more per day from passenger preference. That case study sells itself to the next SACCO. Uganda has over 6,000 registered boda SACCOs. Once two or three adopt it, KCCA mandate follows naturally.</div>
    </div>
</div>

<!-- Simple JS to toggle FAQ answers -->
<script>
document.querySelectorAll('.faq-question').forEach(function(q) {
    q.addEventListener('click', function() {
        this.parentElement.classList.toggle('open');
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
