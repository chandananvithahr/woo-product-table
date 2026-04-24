# Product #2 — WooCommerce Plugin
**Priority:** Build SECOND (14-21 days to first sale, self-distributes forever)
**Model:** Annual license ($79/yr) via WP.org free listing + Freemius/Gumroad upsell

---

## The Problem
WooCommerce store owners have feature requests going unanswered for years on the official forums. Barn2 Plugins built 19 plugins solving these exact gaps — averaging $8K/mo per plugin, $150K/mo total.

## The Buyer
WooCommerce store owners: e-commerce businesses, small retailers, agencies building Woo stores. Price-insensitive on $79/yr if it saves 30 minutes/week.

## Which Plugin to Build
**Target: "Product Table / Quick Order Form for WooCommerce"**

Why this one:
- Most-requested WooCommerce feature not natively supported
- Barn2 charges $99/yr for their version — proof of market
- You can differentiate: add AI-powered "smart sort by purchase history"
- Businesses with large catalogs (100+ products) desperately need this
- Search volume: "woocommerce product table" = 2,400/mo, low competition

**What it does:** Displays products in a sortable, filterable table with quantity inputs and "Add all to cart" — instead of the default grid layout.

---

## Tech Stack
- **Language:** PHP + JavaScript (WooCommerce is PHP)
- **Build tool:** None needed for v1 — pure PHP + vanilla JS
- **Payment:** Freemius (best for WP plugins — handles licensing, updates, renewals automatically)
- **Hosting for docs:** GitHub Pages (free)
- **Plugin hosting:** WordPress.org (free, gives you credibility + search traffic)
- **Total cost:** $12 domain + $0/mo (Freemius takes 0% until $1K MRR, then 3%)

---

## Plugin Structure
```
woo-product-table/
  woo-product-table.php        # Main plugin file
  includes/
    class-table-renderer.php   # Renders the table HTML
    class-ajax-handler.php     # Handles cart add via AJAX
    class-settings.php         # Admin settings page
  assets/
    js/table.js                # Sorting, filtering, qty inputs
    css/table.css              # Table styles
  readme.txt                   # WP.org listing (critical for SEO)
```

---

## 30-Day Day-by-Day Plan

### Week 1 — Build Core (Days 1-7)
- **Day 1:** Set up local WordPress + WooCommerce dev environment (LocalWP — free)
- **Day 2:** Build basic table renderer — outputs products as HTML table via `[product_table]` shortcode
- **Day 3:** Add sorting (by price, name, rating) — pure JS, no library needed
- **Day 4:** Add filtering (by category, in-stock only) + quantity input per row
- **Day 5:** Add "Add selected to cart" AJAX button
- **Day 6:** Admin settings page — choose columns, default sort, items per page
- **Day 7:** Test on 5 WooCommerce demo stores. Fix bugs.

### Week 2 — Polish + Launch Prep (Days 8-14)
- **Day 8:** Set up Freemius for license management (free tier)
- **Day 9:** Build pro features gate: search/filter bar, custom columns, CSV export (pro only)
- **Day 10:** Write WP.org readme.txt — this is your SEO page. Keywords: "woocommerce product table", "woocommerce quick order form", "woocommerce table layout"
- **Day 11:** Create 3 screenshots for WP.org listing
- **Day 12:** Submit to WordPress.org plugin directory (review takes 3-5 days)
- **Day 13:** Set up docs site (GitHub Pages or Notion public page)
- **Day 14:** Set up pricing page: Free (basic table) / Pro $79/yr (search, filter, CSV, multisite)

### Week 3 — Launch (Days 15-21)
- **Day 15:** Plugin approved on WP.org — goes live
- **Day 16:** Post on r/woocommerce: "I built a free product table plugin after not finding a good free option"
- **Day 17:** Post on r/Wordpress, r/webdev
- **Day 18:** Email 20 WooCommerce agencies found on Clutch.co: "free plugin your clients will love"
- **Day 19:** Post in WooCommerce Facebook groups (100K+ members)
- **Day 20:** Reach out to WooCommerce YouTubers for a review
- **Day 21:** First sales should start coming in from organic WP.org search

### Week 4 — Iterate (Days 22-30)
- Respond to every support request same day (builds reviews = more downloads)
- Add features users request
- Target: 100 active installs, 5-10 paid licenses ($400-800)

---

## Launch Playbook
1. **WP.org listing:** Optimize readme.txt like an App Store listing — keywords in title, first paragraph, tags
2. **Reddit:** Genuine post, no hard sell. "I needed this, built it, sharing for free"
3. **WP agencies:** Cold email 50 agencies — they buy licenses for ALL their clients = 10x multiplier
4. **YouTube SEO:** "WooCommerce product table free plugin" gets searched — make a 5-min Loom, upload to YouTube
5. **Review asks:** Email every user who activates the free version after 14 days — ask for a WP.org review

---

## Revenue Model
| Tier | Price | Target |
|---|---|---|
| Free | $0 | 500+ installs (builds trust + reviews) |
| Pro Single Site | $79/yr | Primary revenue |
| Pro 5 Sites | $149/yr | Agency tier |
| Pro Unlimited | $249/yr | Large agencies |

**Target MRR by Month 3:** $3-8K (based on Barn2 per-plugin average)

---

## Costs
| Item | Cost |
|---|---|
| Domain for docs | $12/yr |
| LocalWP dev environment | $0 |
| WordPress.org listing | $0 |
| Freemius payments | 3% after $1K MRR |
| **Total upfront** | **$12** |

---

## Competitive Advantage Over Barn2
- Barn2 charges $99/yr — undercut at $79/yr
- Add AI feature: "Smart Sort" — sorts products by what this store's customers buy most (uses WC order data)
- Better free tier — Barn2's free version is very limited
- Faster support (they're a team, you respond in hours)

---

## Success Metrics
- Week 3: Plugin live on WP.org, first 50 installs
- Month 1: 200 installs, 5-10 pro licenses ($400-800)
- Month 3: 1K installs, 50 pro licenses ($4K/mo)
- Month 6: $8K/mo (matches Barn2 per-plugin average)
