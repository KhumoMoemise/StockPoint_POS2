<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const root = ref<HTMLElement | null>(null);
const scrolled = ref(false);
const navOpen = ref(false);
const faqOpen = ref<number | null>(0);

function onScroll() {
    scrolled.value = window.scrollY > 40;
}

function toggleFaq(index: number) {
    faqOpen.value = faqOpen.value === index ? null : index;
}

const faqs = [
    {
        q: 'Does StockPoint work with my barcode scanner?',
        a: 'Yes. Any USB or Bluetooth scanner that acts as a keyboard works out of the box — scan a code in the search field and the product is added instantly.',
    },
    {
        q: 'Is VAT handled automatically?',
        a: 'Absolutely. Prices are VAT-inclusive at 15%, and receipts show the VAT portion separately, keeping you SARS-compliant.',
    },
    {
        q: 'Can I control what my cashiers can see?',
        a: 'Yes. Role-based access is enforced on the server: cashiers get the POS workspace only, while admins manage products, stock, suppliers and reports.',
    },
    {
        q: 'Do I need special hardware?',
        a: 'No. StockPoint runs in any modern browser on a tablet, laptop or POS terminal. Add a receipt printer and scanner when you are ready.',
    },
    {
        q: 'Can I export my data?',
        a: 'Yes — one-click CSV exports for products and sales, perfect for your accountant or backup routine.',
    },
];

let observer: IntersectionObserver | null = null;

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    observer?.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12 },
    );

    root.value?.querySelectorAll('.reveal').forEach((el) => observer?.observe(el));
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    observer?.disconnect();
});
</script>

<template>
    <Head title="StockPoint — POS & Inventory Management for Growing Stores" />

    <div class="landing" ref="root">
        <nav class="nav" :class="{ scrolled }">
            <div class="wrap nav-inner">
                <a class="brand" href="#top"><span class="brand-mark">▦</span> StockPoint</a>
                <div class="nav-links" :class="{ open: navOpen }">
                    <a href="#features" @click="navOpen = false">Features</a>
                    <a href="#roles" @click="navOpen = false">Who it's for</a>
                    <a href="#pricing" @click="navOpen = false">Pricing</a>
                    <a href="#faq" @click="navOpen = false">FAQ</a>
                    <Link :href="route('login')" class="nav-signin">Sign in</Link>
                    <Link :href="route('register')" class="btn btn-primary" style="padding: 10px 18px">Start free trial</Link>
                </div>
                <button class="burger" @click="navOpen = !navOpen">☰</button>
            </div>
        </nav>

        <header class="hero" id="top">
            <div class="wrap hero-grid">
                <div>
                    <span class="eyebrow"><i></i> Built for South African retailers</span>
                    <h1>Run your whole store from <span>one login</span>.</h1>
                    <p class="lead">
                        StockPoint combines a lightning-fast cashier POS with live inventory, supplier management and instant
                        reports — so you always know what's on your shelves and what's in your till.
                    </p>
                    <div class="hero-cta">
                        <Link :href="route('register')" class="btn btn-primary">Start your free trial →</Link>
                        <a href="#features" class="btn btn-ghost">See how it works</a>
                    </div>
                    <div class="hero-note">No card required · 14-day free trial · Cancel anytime</div>
                    <div class="hero-stats">
                        <div><strong>3s</strong><span>Average checkout</span></div>
                        <div><strong>99.9%</strong><span>Uptime SLA</span></div>
                        <div><strong>24/7</strong><span>Local support</span></div>
                    </div>
                </div>

                <div class="mockup">
                    <div class="mockup-frame">
                        <div class="mockbar"><i></i><i></i><i></i></div>
                        <div class="mock-body">
                            <div class="mock-side"><b class="on"></b><b></b><b></b><b></b><b></b></div>
                            <div class="mock-main">
                                <div class="mock-cards">
                                    <div class="mock-card"><small>Today's revenue</small><strong>R 4 285</strong><em></em></div>
                                    <div class="mock-card"><small>Products</small><strong>1 240</strong><em></em></div>
                                    <div class="mock-card"><small>Low stock alerts</small><strong>7</strong><em></em></div>
                                </div>
                                <div class="mock-chart">
                                    <svg viewBox="0 0 400 120" preserveAspectRatio="none">
                                        <defs>
                                            <linearGradient id="g" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0" stop-color="#2563eb" stop-opacity=".25" />
                                                <stop offset="1" stop-color="#2563eb" stop-opacity="0" />
                                            </linearGradient>
                                        </defs>
                                        <path
                                            d="M0,95 C40,80 60,88 90,70 C120,52 150,64 180,48 C210,32 240,44 270,30 C300,16 340,26 400,12 L400,120 L0,120 Z"
                                            fill="url(#g)"
                                        />
                                        <path
                                            d="M0,95 C40,80 60,88 90,70 C120,52 150,64 180,48 C210,32 240,44 270,30 C300,16 340,26 400,12"
                                            fill="none"
                                            stroke="#2563eb"
                                            stroke-width="2.5"
                                        />
                                        <circle cx="270" cy="30" r="4" fill="#2563eb" />
                                        <circle cx="400" cy="12" r="4" fill="#16845b" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="float-chip chip1">
                        <div class="chip-ico" style="background: #eaf8f1">✅</div>
                        <div>Sale completed<small>R 246.50 · Card</small></div>
                    </div>
                    <div class="float-chip chip2">
                        <div class="chip-ico" style="background: #fff4df">⚠️</div>
                        <div>Low stock alert<small>White Sugar 2kg · 3 left</small></div>
                    </div>
                </div>
            </div>
        </header>

        <div class="logo-strip">
            <div class="wrap">
                <span>TRUSTED BY RETAILERS ACROSS</span>
                <span style="color: #6b7a94">Cape Town</span><span style="color: #6b7a94">Johannesburg</span
                ><span style="color: #6b7a94">Durban</span><span style="color: #6b7a94">Pretoria</span
                ><span style="color: #6b7a94">Gqeberha</span>
            </div>
        </div>

        <section id="features">
            <div class="wrap">
                <div class="sec-head reveal">
                    <span class="sec-tag">Everything in one place</span>
                    <h2>Less admin. More sales.</h2>
                    <p>StockPoint replaces the spreadsheet, the paper receipt book and the guesswork with one system your whole team can use.</p>
                </div>
                <div class="features-grid">
                    <div class="feature reveal">
                        <div class="feature-ico">⚡</div>
                        <h3>Blazing-fast checkout</h3>
                        <p>A touch-friendly POS with barcode scanner support, instant search and one-tap payment. Card, cash and Instant EFT built in.</p>
                    </div>
                    <div class="feature reveal">
                        <div class="feature-ico">📦</div>
                        <h3>Live inventory control</h3>
                        <p>Stock levels update with every sale. Receive goods, adjust counts and set reorder points so you never run dry.</p>
                    </div>
                    <div class="feature reveal">
                        <div class="feature-ico">⚠️</div>
                        <h3>Smart low-stock alerts</h3>
                        <p>Get warned the moment a product hits its reorder level — on the dashboard and in your alerts panel.</p>
                    </div>
                    <div class="feature reveal">
                        <div class="feature-ico">🚚</div>
                        <h3>Supplier management</h3>
                        <p>Keep every supplier's contacts, emails and phone numbers one click away when it's time to restock.</p>
                    </div>
                    <div class="feature reveal">
                        <div class="feature-ico">📊</div>
                        <h3>Reports &amp; analytics</h3>
                        <p>Daily revenue, transaction counts, units sold and full CSV exports — ready for your accountant in one click.</p>
                    </div>
                    <div class="feature reveal">
                        <div class="feature-ico">🔐</div>
                        <h3>Role-based access</h3>
                        <p>Cashiers see only the till. Admins see everything. Permissions enforced on the server, not just the screen.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="roles" id="roles">
            <div class="wrap">
                <div class="sec-head reveal">
                    <span class="sec-tag">Two dashboards, one system</span>
                    <h2>Built for the people who run your store</h2>
                    <p>Each role gets a focused workspace with exactly the tools they need — and nothing they don't.</p>
                </div>
                <div class="roles-grid">
                    <div class="role-card reveal">
                        <span class="tag">Admin dashboard</span>
                        <h3>For owners &amp; managers</h3>
                        <p>The full command centre: catalogue, stock movements, suppliers, invoices, exports and store settings.</p>
                        <ul>
                            <li><i>✓</i> Register and edit products, SKUs &amp; barcodes</li>
                            <li><i>✓</i> Receive stock and run stock adjustments</li>
                            <li><i>✓</i> View every sale and reprint invoices</li>
                            <li><i>✓</i> Weekly sales charts &amp; CSV reports</li>
                            <li><i>✓</i> Configure business details for receipts</li>
                        </ul>
                    </div>
                    <div class="role-card cashier reveal">
                        <span class="tag">Cashier POS</span>
                        <h3>For the front line</h3>
                        <p>A fast, focused point of sale that keeps the queue moving and the stock accurate with every transaction.</p>
                        <ul>
                            <li><i>✓</i> Search or scan products to build a sale</li>
                            <li><i>✓</i> Automatic change calculation</li>
                            <li><i>✓</i> VAT (15%) computed automatically</li>
                            <li><i>✓</i> Printable customer receipts</li>
                            <li><i>✓</i> Live sales and shift statistics</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="wrap">
                <div class="sec-head reveal">
                    <span class="sec-tag">Up and running today</span>
                    <h2>Live in three simple steps</h2>
                </div>
                <div class="steps">
                    <div class="step reveal">
                        <div class="feature-ico">🖥️</div>
                        <h3>Deploy the system</h3>
                        <p>Install StockPoint on your server or our managed cloud. Laravel-powered, secure by default, VAT-ready.</p>
                    </div>
                    <div class="step reveal">
                        <div class="feature-ico">🏷️</div>
                        <h3>Load your catalogue</h3>
                        <p>Import products, barcodes and suppliers in bulk — or add them one by one in the admin dashboard.</p>
                    </div>
                    <div class="step reveal">
                        <div class="feature-ico">💳</div>
                        <h3>Start selling</h3>
                        <p>Hand your cashiers their logins and start ringing up sales with live stock and instant receipts.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="pricing" style="background: #fff">
            <div class="wrap">
                <div class="sec-head reveal">
                    <span class="sec-tag">Simple pricing</span>
                    <h2>Pricing that fits a small business</h2>
                    <p>All prices in South African rand. No setup fees, no hidden costs.</p>
                </div>
                <div class="pricing-grid">
                    <div class="price-card reveal">
                        <h3>Starter</h3>
                        <div class="amount">R 299<small>/mo</small></div>
                        <p>For single tills and market stalls.</p>
                        <ul>
                            <li><i>✓</i> 1 cashier + 1 admin</li>
                            <li><i>✓</i> Up to 500 products</li>
                            <li><i>✓</i> Cash &amp; card payments</li>
                            <li class="no"><i>✕</i> CSV exports</li>
                            <li class="no"><i>✕</i> Priority support</li>
                        </ul>
                        <Link :href="route('register')" class="btn btn-outline">Choose Starter</Link>
                    </div>
                    <div class="price-card popular reveal">
                        <span class="pop-badge">Most popular</span>
                        <h3>Growing Store</h3>
                        <div class="amount">R 599<small>/mo</small></div>
                        <p>For busy shops that need the full toolkit.</p>
                        <ul>
                            <li><i>✓</i> 5 staff accounts, custom roles</li>
                            <li><i>✓</i> Unlimited products &amp; suppliers</li>
                            <li><i>✓</i> Reports, charts &amp; CSV exports</li>
                            <li><i>✓</i> Instant EFT payments</li>
                            <li><i>✓</i> Email support, 4h response</li>
                        </ul>
                        <Link :href="route('register')" class="btn btn-primary" style="justify-content: center">Choose Growing →</Link>
                    </div>
                    <div class="price-card reveal">
                        <h3>Multi-Store</h3>
                        <div class="amount">R 1 499<small>/mo</small></div>
                        <p>For chains, franchises &amp; wholesalers.</p>
                        <ul>
                            <li><i>✓</i> Unlimited staff &amp; stores</li>
                            <li><i>✓</i> Cross-store stock transfers</li>
                            <li><i>✓</i> Consolidated group reports</li>
                            <li><i>✓</i> API access &amp; integrations</li>
                            <li><i>✓</i> Dedicated account manager</li>
                        </ul>
                        <Link :href="route('info.contact')" class="btn btn-outline">Talk to sales</Link>
                    </div>
                </div>
            </div>
        </section>

        <section id="faq">
            <div class="wrap">
                <div class="sec-head reveal">
                    <span class="sec-tag">Questions</span>
                    <h2>Frequently asked questions</h2>
                </div>
                <div class="faq-list">
                    <div v-for="(item, index) in faqs" :key="index" class="faq reveal" :class="{ open: faqOpen === index }">
                        <button @click="toggleFaq(index)">{{ item.q }}<i>+</i></button>
                        <div class="ans"><p>{{ item.a }}</p></div>
                    </div>
                </div>
            </div>
        </section>

        <section style="padding-top: 0">
            <div class="wrap">
                <div class="cta-band reveal">
                    <h2>Ready to take control of your store?</h2>
                    <p>Join hundreds of South African retailers who ditched the spreadsheet. Set up StockPoint today and sell smarter tomorrow.</p>
                    <Link :href="route('register')" class="btn btn-white" style="font-size: 15px; padding: 15px 34px">Start your 14-day free trial →</Link>
                </div>
            </div>
        </section>

        <footer>
            <div class="wrap">
                <div class="foot-grid">
                    <div>
                        <a class="brand" href="#top" style="color: #fff; margin-bottom: 14px"><span class="brand-mark">▦</span> StockPoint</a>
                        <p style="max-width: 280px; margin-top: 14px">The complete point of sale and inventory system for growing South African stores.</p>
                    </div>
                    <div>
                        <h4>Product</h4>
                        <a href="#features">Features</a><a href="#pricing">Pricing</a><a href="#roles">Dashboards</a>
                    </div>
                    <div>
                        <h4>Company</h4>
                        <Link :href="route('info.about')">About us</Link>
                        <Link :href="route('info.careers')">Careers</Link>
                        <Link :href="route('info.contact')">Contact</Link>
                    </div>
                    <div>
                        <h4>Legal</h4>
                        <Link :href="route('info.privacy')">Privacy policy</Link>
                        <Link :href="route('info.terms')">Terms of service</Link>
                        <Link :href="route('info.popia')">POPIA compliance</Link>
                    </div>
                </div>
                <div class="foot-bottom">
                    <span>© 2026 StockPoint (Pty) Ltd. All rights reserved.</span>
                    <span>Made in South Africa 🇿🇦</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.landing {
    --navy: #0f1b33;
    --navy2: #14213d;
    --blue: #2563eb;
    --blue2: #1d4ed8;
    --blue-soft: #eff6ff;
    --ink: #172033;
    --muted: #64748b;
    --line: #e7ecf3;
    --green: #16845b;
    --amber: #b7791f;
    --red: #d64545;
    --bg: #f7f9fd;
    --white: #fff;
    --radius: 16px;
    --shadow: 0 10px 30px rgba(15, 27, 51, 0.08);
    --shadow-lg: 0 30px 70px rgba(15, 27, 51, 0.18);
    background: var(--bg);
    color: var(--ink);
    line-height: 1.6;
    overflow-x: hidden;
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
}
.landing *,
.landing *::before,
.landing *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
.landing a {
    text-decoration: none;
    color: inherit;
}
.landing button {
    font: inherit;
    cursor: pointer;
}
.landing img {
    max-width: 100%;
}
.landing .wrap {
    width: min(1140px, 92%);
    margin: 0 auto;
}
.landing .nav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 50;
    transition: 0.25s;
}
.landing .nav.scrolled {
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 14px rgba(15, 27, 51, 0.08);
}
.landing .nav-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 0;
}
.landing .brand {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 800;
    font-size: 19px;
    color: #fff;
}
.landing .nav.scrolled .brand {
    color: var(--navy);
}
.landing .brand-mark {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: var(--blue);
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 18px;
    box-shadow: 0 4px 12px #2563eb55;
}
.landing .nav-links {
    display: flex;
    gap: 22px;
    align-items: center;
}
.landing .nav-links a {
    color: #c9d5ea;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
}
.landing .nav-links a:hover {
    color: #fff;
}
.landing .nav.scrolled .nav-links a {
    color: var(--muted);
}
.landing .nav.scrolled .nav-links a:hover {
    color: var(--blue);
}
.landing .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 22px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    border: 0;
    transition: 0.2s;
}
.landing .btn-primary {
    background: var(--blue);
    color: #fff;
    box-shadow: 0 6px 18px #2563eb44;
}
.landing .btn-primary:hover {
    background: var(--blue2);
    transform: translateY(-1px);
}
.landing .btn-ghost {
    background: transparent;
    color: #fff;
    border: 1px solid #ffffff44;
}
.landing .btn-ghost:hover {
    background: #ffffff14;
}
.landing .btn-outline {
    color: var(--navy);
    border: 1px solid var(--line);
    background: #fff;
    justify-content: center;
}
.landing .btn-outline:hover {
    border-color: #bcd3ff;
    color: var(--blue);
}
.landing .btn-white {
    background: #fff;
    color: var(--navy);
}
.landing .btn-white:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px #0002;
}
.landing .burger {
    display: none;
    background: none;
    border: 0;
    color: #fff;
    font-size: 22px;
}
.landing .nav.scrolled .burger {
    color: var(--navy);
}
.landing .hero {
    position: relative;
    background: radial-gradient(1000px 500px at 75% -10%, #2b4a8f33, transparent), linear-gradient(160deg, #0d1830, #14213d 55%, #1a2c52);
    color: #fff;
    padding: 150px 0 90px;
    overflow: hidden;
}
.landing .hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 0v60M0 30h60' stroke='%23ffffff' stroke-opacity='.05'/%3E%3C/svg%3E");
    pointer-events: none;
}
.landing .hero-grid {
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    gap: 50px;
    align-items: center;
    position: relative;
    z-index: 1;
}
.landing .eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff14;
    border: 1px solid #ffffff22;
    padding: 7px 14px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: #bcd0f5;
    margin-bottom: 22px;
}
.landing .eyebrow i {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #4ade80;
    display: inline-block;
    animation: pulse 1.6s infinite;
}
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.35;
    }
}
.landing .hero h1 {
    font-size: clamp(32px, 4.6vw, 52px);
    line-height: 1.12;
    letter-spacing: -1.4px;
    font-weight: 800;
}
.landing .hero h1 span {
    background: linear-gradient(90deg, #7aa8ff, #4ade80);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.landing .hero p.lead {
    margin: 20px 0 30px;
    color: #b7c6e2;
    font-size: 16px;
    max-width: 520px;
}
.landing .hero-cta {
    display: flex;
    gap: 13px;
    flex-wrap: wrap;
    align-items: center;
}
.landing .hero-note {
    margin-top: 15px;
    font-size: 12px;
    color: #8fa2c7;
}
.landing .hero-stats {
    display: flex;
    gap: 34px;
    margin-top: 44px;
    flex-wrap: wrap;
}
.landing .hero-stats div strong {
    font-size: 24px;
    font-weight: 800;
    display: block;
}
.landing .hero-stats div span {
    font-size: 11px;
    color: #93a5c8;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
.landing .mockup {
    position: relative;
}
.landing .mockup-frame {
    background: #fff;
    border-radius: 18px;
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    transform: perspective(1400px) rotateY(-7deg) rotateX(2deg);
    transition: transform 0.5s;
}
.landing .mockup:hover .mockup-frame {
    transform: perspective(1400px) rotateY(-3deg) rotateX(1deg);
}
.landing .mockbar {
    background: #eef2f8;
    padding: 11px 15px;
    display: flex;
    gap: 6px;
}
.landing .mockbar i {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #d3dbe8;
    display: block;
}
.landing .mock-body {
    display: grid;
    grid-template-columns: 88px 1fr;
    min-height: 380px;
}
.landing .mock-side {
    background: var(--navy);
    padding: 16px 10px;
    display: flex;
    flex-direction: column;
    gap: 9px;
}
.landing .mock-side b {
    width: 100%;
    height: 28px;
    border-radius: 8px;
    background: #ffffff17;
    display: block;
}
.landing .mock-side b.on {
    background: #3978f6;
}
.landing .mock-main {
    padding: 18px;
    background: #f7f9fd;
}
.landing .mock-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 9px;
    margin-bottom: 12px;
}
.landing .mock-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 11px;
    box-shadow: 0 3px 8px rgba(15, 27, 51, 0.04);
}
.landing .mock-card small {
    color: var(--muted);
    font-size: 8px;
    display: block;
}
.landing .mock-card strong {
    font-size: 15px;
    letter-spacing: -0.4px;
}
.landing .mock-card em {
    display: block;
    height: 5px;
    border-radius: 4px;
    margin-top: 8px;
    background: linear-gradient(90deg, var(--green), #4ade80);
    font-style: normal;
}
.landing .mock-card:nth-child(2) em {
    background: linear-gradient(90deg, var(--blue), #93c5fd);
}
.landing .mock-card:nth-child(3) em {
    background: linear-gradient(90deg, var(--amber), #f6d365);
}
.landing .mock-chart {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 14px;
    height: 150px;
    position: relative;
    overflow: hidden;
}
.landing .mock-chart svg {
    width: 100%;
    height: 100%;
}
.landing .float-chip {
    position: absolute;
    background: #fff;
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
    padding: 12px 16px;
    display: flex;
    gap: 11px;
    align-items: center;
    font-size: 12px;
    font-weight: 700;
    animation: float 5s ease-in-out infinite;
}
.landing .float-chip small {
    display: block;
    font-weight: 600;
    color: var(--muted);
    font-size: 10px;
}
.landing .chip1 {
    top: 38px;
    right: -14px;
}
.landing .chip2 {
    bottom: 34px;
    left: -22px;
    animation-delay: 2.2s;
}
.landing .chip-ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    font-size: 16px;
}
@keyframes float {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}
.landing section {
    padding: 90px 0;
}
.landing .sec-head {
    max-width: 640px;
    margin: 0 auto 54px;
    text-align: center;
}
.landing .sec-tag {
    color: var(--blue);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.6px;
    text-transform: uppercase;
    display: block;
    margin-bottom: 12px;
}
.landing .sec-head h2 {
    font-size: clamp(26px, 3.4vw, 38px);
    letter-spacing: -1px;
    line-height: 1.2;
}
.landing .sec-head p {
    color: var(--muted);
    margin-top: 14px;
    font-size: 15px;
}
.landing .logo-strip {
    padding: 34px 0;
    border-bottom: 1px solid var(--line);
    background: #fff;
}
.landing .logo-strip .wrap {
    display: flex;
    gap: 44px;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    color: #9aa7bd;
    font-weight: 800;
    font-size: 14px;
    letter-spacing: 0.6px;
}
.landing .features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}
.landing .feature {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 28px;
    transition: 0.25s;
    position: relative;
    overflow: hidden;
}
.landing .feature:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: #bcd3ff;
}
.landing .feature-ico {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: var(--blue-soft);
    display: grid;
    place-items: center;
    font-size: 21px;
    margin-bottom: 18px;
}
.landing .feature h3 {
    font-size: 16px;
    margin-bottom: 9px;
    letter-spacing: -0.3px;
}
.landing .feature p {
    font-size: 13px;
    color: var(--muted);
}
.landing .roles {
    background: linear-gradient(180deg, #fff, var(--bg));
}
.landing .roles-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
}
.landing .role-card {
    background: var(--navy);
    border-radius: 20px;
    padding: 36px;
    color: #dbe6f8;
    position: relative;
    overflow: hidden;
}
.landing .role-card.cashier {
    background: linear-gradient(150deg, #123a2c, #16845b);
}
.landing .role-card::after {
    content: '';
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: #ffffff0d;
    top: -90px;
    right: -90px;
}
.landing .role-card .tag {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    background: #ffffff1c;
    padding: 6px 12px;
    border-radius: 20px;
    display: inline-block;
    margin-bottom: 18px;
}
.landing .role-card h3 {
    color: #fff;
    font-size: 22px;
    letter-spacing: -0.5px;
    margin-bottom: 8px;
}
.landing .role-card > p {
    font-size: 13px;
    color: #b9c9e4;
    margin-bottom: 22px;
    max-width: 400px;
}
.landing .role-card.cashier > p {
    color: #c8ecdd;
}
.landing .role-card ul {
    list-style: none;
    display: grid;
    gap: 11px;
    font-size: 13px;
}
.landing .role-card li {
    display: flex;
    gap: 10px;
    align-items: center;
}
.landing .role-card li i {
    color: #4ade80;
    font-style: normal;
    font-weight: 900;
}
.landing .steps {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    counter-reset: step;
}
.landing .step {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 30px;
    position: relative;
}
.landing .step::before {
    counter-increment: step;
    content: '0' counter(step);
    position: absolute;
    top: 22px;
    right: 24px;
    font-size: 44px;
    font-weight: 800;
    color: #edf2fa;
    letter-spacing: -2px;
}
.landing .step h3 {
    font-size: 16px;
    margin: 16px 0 8px;
    position: relative;
}
.landing .step p {
    font-size: 13px;
    color: var(--muted);
}
.landing .pricing-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    align-items: stretch;
}
.landing .price-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 20px;
    padding: 34px 30px;
    display: flex;
    flex-direction: column;
    transition: 0.25s;
}
.landing .price-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}
.landing .price-card.popular {
    border: 2px solid var(--blue);
    position: relative;
    box-shadow: 0 20px 50px #2563eb22;
}
.landing .pop-badge {
    position: absolute;
    top: -13px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--blue);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 6px 16px;
    border-radius: 20px;
}
.landing .price-card h3 {
    font-size: 15px;
}
.landing .price-card .amount {
    font-size: 42px;
    font-weight: 800;
    letter-spacing: -2px;
    margin: 14px 0 4px;
}
.landing .price-card .amount small {
    font-size: 14px;
    color: var(--muted);
    font-weight: 600;
    letter-spacing: 0;
}
.landing .price-card > p {
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 24px;
}
.landing .price-card ul {
    list-style: none;
    display: grid;
    gap: 11px;
    font-size: 13px;
    margin-bottom: 28px;
    flex: 1;
}
.landing .price-card li {
    display: flex;
    gap: 9px;
    align-items: center;
    color: #46536a;
}
.landing .price-card li i {
    color: var(--green);
    font-style: normal;
    font-weight: 900;
}
.landing .price-card li.no {
    color: #b0bacb;
    text-decoration: line-through;
}
.landing .faq-list {
    max-width: 760px;
    margin: 0 auto;
    display: grid;
    gap: 11px;
}
.landing .faq {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 13px;
    overflow: hidden;
}
.landing .faq button {
    width: 100%;
    background: none;
    border: 0;
    padding: 19px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    font-weight: 700;
    text-align: left;
    gap: 12px;
    color: var(--ink);
}
.landing .faq button i {
    transition: 0.25s;
    color: var(--blue);
    font-style: normal;
    font-size: 18px;
}
.landing .faq .ans {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}
.landing .faq .ans p {
    padding: 0 22px 20px;
    font-size: 13px;
    color: var(--muted);
}
.landing .faq.open .ans {
    max-height: 260px;
}
.landing .faq.open button i {
    transform: rotate(45deg);
}
.landing .cta-band {
    background: radial-gradient(600px 300px at 20% 0%, #2b4a8f44, transparent), linear-gradient(150deg, #0d1830, #1a2c52);
    border-radius: 24px;
    padding: 64px 40px;
    text-align: center;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.landing .cta-band h2 {
    font-size: clamp(26px, 3.6vw, 40px);
    letter-spacing: -1px;
}
.landing .cta-band p {
    color: #b7c6e2;
    margin: 16px auto 30px;
    max-width: 520px;
    font-size: 15px;
}
.landing footer {
    background: var(--navy);
    color: #93a5c8;
    padding: 56px 0 30px;
    font-size: 13px;
}
.landing .foot-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr 1fr;
    gap: 34px;
    margin-bottom: 40px;
}
.landing .foot-grid h4 {
    color: #fff;
    font-size: 13px;
    margin-bottom: 15px;
    letter-spacing: 0.4px;
}
.landing .foot-grid a {
    display: block;
    color: #93a5c8;
    margin-bottom: 9px;
    transition: 0.2s;
}
.landing .foot-grid a:hover {
    color: #fff;
}
.landing .foot-bottom {
    border-top: 1px solid #ffffff14;
    padding-top: 24px;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 12px;
}
.landing .reveal {
    opacity: 0;
    transform: translateY(26px);
    transition: 0.7s cubic-bezier(0.2, 0.7, 0.3, 1);
}
.landing .reveal.in {
    opacity: 1;
    transform: none;
}
@media (max-width: 960px) {
    .landing .hero-grid,
    .landing .roles-grid {
        grid-template-columns: 1fr;
    }
    .landing .features-grid,
    .landing .steps,
    .landing .pricing-grid {
        grid-template-columns: 1fr 1fr;
    }
    .landing .mockup {
        max-width: 560px;
        margin: 0 auto;
    }
    .landing .foot-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 680px) {
    .landing .nav-links {
        display: none;
        position: absolute;
        top: 64px;
        left: 0;
        right: 0;
        background: #fff;
        flex-direction: column;
        padding: 18px;
        gap: 14px;
        box-shadow: var(--shadow-lg);
    }
    .landing .nav-links.open {
        display: flex;
    }
    .landing .nav-links a {
        color: var(--ink) !important;
    }
    .landing .burger {
        display: block;
    }
    .landing .features-grid,
    .landing .steps,
    .landing .pricing-grid {
        grid-template-columns: 1fr;
    }
    .landing .hero {
        padding: 120px 0 60px;
    }
    .landing section {
        padding: 60px 0;
    }
    .landing .chip1 {
        right: 0;
    }
    .landing .chip2 {
        left: 0;
    }
}
</style>
