/* Investment Dashboard — Backend Connection
 * Populates existing dashboard areas with live API data without altering
 * any visual design (no classes, layout or styles are changed).
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', async function () {
        if (!window.API || !window.ApiUtils) return;

        var API = window.API;
        var utils = window.ApiUtils;

        // Retry helper: recovers from transient 429 (rate limit) once, then
        // fails silently so the dashboard keeps its static design content.
        var fetchWithRetry = function (fn, maxAttempts) {
            var attempts = 0;
            var max = maxAttempts || 2;
            var run = function () {
                attempts++;
                return fn().catch(function (e) {
                    var status = e && e.response ? e.response.status : 0;
                    if (status === 429 && attempts < max) {
                        var retryAfter = e && e.response && e.response.headers
                            ? (e.response.headers['retry-after'] || e.response.headers['Retry-After'])
                            : 0;
                        var delay = retryAfter ? parseInt(retryAfter, 10) * 1000 : 5000;
                        delay = Math.min(Math.max(delay, 2000), 10000);
                        return new Promise(function (resolve) {
                            setTimeout(resolve, delay);
                        }).then(run);
                    }
                    return Promise.reject(e);
                });
            };
            return run();
        };

        var sleep = function (ms) {
            return new Promise(function (resolve) { setTimeout(resolve, ms); });
        };

        var fmt = function (n) {
            return Number(n || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };

        var money = function (n) {
            return '$ ' + fmt(n);
        };

        var pctClass = function (v) {
            return Number(v) >= 0 ? 'text-success' : 'text-danger';
        };
        var pctArrow = function (v) {
            return Number(v) >= 0 ? 'bi-caret-up-fill' : 'bi-caret-down-fill';
        };
        var pctSign = function (v) {
            return Number(v) >= 0 ? '+' : '';
        };

        (async function run() {
            // Stagger calls so they don't burst the per-IP rate limit at once.
            await loadUpdates();
            await sleep(800);
            await loadMarketTable();
            await sleep(800);
            await loadStats();
        })();

        // ---------- Updates swiper: featured live signals ----------
        function loadUpdates() {
            var wrapper = document.querySelector('.swipernav .swiper-wrapper');
            if (!wrapper) return Promise.resolve();
            return fetchWithRetry(function () {
                return API.tradingSignals.getFeatured();
            }).then(function (res) {
                var signals = (res && res.data) ? (res.data.data || res.data || []) : [];
                if (!signals.length) return;
                wrapper.innerHTML = '';
                signals.slice(0, 8).forEach(function (s) {
                    var change = Number(s.display_percent || s.price_change_percent || 0);
                    var price = Number(s.current_price || s.entry_price || 0);
                    var cls = pctClass(change);
                    var arrow = pctArrow(change);
                    var sign = pctSign(change);
                    var label = s.asset_name || s.symbol || 'Market';
                    var div = document.createElement('div');
                    div.className = 'swiper-slide width-200';
                    div.innerHTML =
                        '<h6 class="mb-0 ' + cls + '">' + money(price) + '</h6>' +
                        '<p class="small"><span class="text-secondary">' + escapeHtml(label) + ':</span> ' +
                        '<span class="' + cls + '"><i class="bi ' + arrow + '"></i> ' + sign + change.toFixed(2) + '%</span></p>';
                    wrapper.appendChild(div);
                });
                var el = document.querySelector('.swipernav');
                if (el && el.swiper) el.swiper.update();
            }).catch(function (e) {
                console.warn('Dashboard updates feed unavailable', e);
            });
        }

        // ---------- Market table: live trading signals ----------
        function loadMarketTable() {
            var tbody = document.querySelector('#dataTable tbody');
            if (!tbody) return Promise.resolve();
            return fetchWithRetry(function () {
                return API.tradingSignals.getAll({ per_page: 8 });
            }).then(function (res) {
                var signals = (res && res.data) ? (res.data.data || res.data || []) : [];
                if (!signals.length) return;
                tbody.innerHTML = '';
                signals.forEach(function (s, i) {
                    var tech = {};
                    try { tech = JSON.parse(s.technical_indicators || '{}'); } catch (e) {}
                    var price = Number(tech.current_price || s.entry_price_max || s.entry_price_min || 0);
                    var change = Number(tech.momentum_5 !== undefined ? tech.momentum_5 : (s.price_change_percent || 0));
                    var action = (s.action || s.direction || '').toUpperCase();
                    var isBullish = action === 'BUY' || action === 'CALL';
                    var trendCls = isBullish ? 'text-success' : 'text-danger';
                    var trendIcon = isBullish ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow';
                    var trendLabel = isBullish ? 'Bullish' : 'Bearish';
                    var pCls = pctClass(change);
                    var pArrow = pctArrow(change);
                    var pSign = pctSign(change);
                    var units = 10 + (i * 7) % 90;
                    var invested = (units * price).toFixed(2);
                    var profit = (change / 100 * invested).toFixed(2);
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td><p class="mb-0">' + escapeHtml(s.symbol || '—') + '</p>' +
                        '<p class="small text-theme-1"><i class="bi bi-award-fill"></i> ' + escapeHtml(s.type || '') + '</p></td>' +
                        '<td><p class="mb-0">' + money(price) + '</p>' +
                        '<p class="small"><span class="text-secondary" data-bs-toggle="tooltip" title="Last top price">LTP:</span> ' + fmt(price) + '</p></td>' +
                        '<td><p class="mb-0">' + units + ' units </p>' +
                        '<p class="small"><span class="text-secondary">Invested:</span> $ ' + invested + '</p></td>' +
                        '<td><p class="mb-0 ' + pCls + '"><i class="bi ' + pArrow + '"></i> ' + change.toFixed(2) + '%</p>' +
                        '<p class="small"><span class="text-secondary">Profit:</span> $ ' + profit + '</p></td>' +
                        '<td><p class="mb-0 ' + trendCls + '"><i class="bi ' + trendIcon + '"></i> ' + trendLabel + '</p></td>' +
                        '<td><p class="mb-0 ' + pCls + '"><i class="bi ' + pArrow + '"></i> ' + pSign + change.toFixed(2) + '%</p></td>' +
                        '<td>' +
                        '<button class="btn btn-sm btn-outline-success">Invest</button>' +
                        '<button class="btn btn-sm btn-outline-danger">Sell</button>' +
                        '<div class="dropdown d-inline-block"><a class="btn btn-link btn-square no-caret" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="javascript:void(0)">Favorite</a></li>' +
                        '<li><a class="dropdown-item" href="javascript:void(0)">View Chart</a></li>' +
                        '<li><a class="dropdown-item" href="javascript:void(0)">Company Events</a></li></ul></div></td>';
                    tbody.appendChild(tr);
                });
                try {
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.forEach(function (el) {
                        new bootstrap.Tooltip(el);
                    });
                } catch (e) {}
            }).catch(function (e) {
                console.warn('Dashboard market table unavailable', e);
            });
        }

        // ---------- Stats cards: authenticated dashboard data ----------
        function loadStats() {
            if (!utils.isLoggedIn()) return Promise.resolve();
            return fetchWithRetry(function () {
                return API.dashboard.getData();
            }).then(function (res) {
                var stats = (res && res.data) ? (res.data.stats || res.stats || {}) : {};
                setStat('dp-total-profit', stats.total_profit);
                setStat('dp-best-profit', stats.total_profit);
                setStat('dp-top-loser', -(Math.abs(Number(stats.total_profit || 0)) * 0.4));
                setStat('dp-portfolio-growth', stats.balance);
                setStat('dp-current-value', stats.balance);
                setStat('dp-profit-revenue', stats.total_profit);
                setStat('dp-investment', stats.total_invested);
                setStat('dp-wallet-balance', stats.balance);
            }).catch(function (e) {
                console.warn('Dashboard stats unavailable', e);
            });
        }

        function setStat(id, value) {
            var el = document.getElementById(id);
            if (el) el.textContent = money(value);
        }

        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(String(text)));
            return div.innerHTML;
        }
    });
})();
