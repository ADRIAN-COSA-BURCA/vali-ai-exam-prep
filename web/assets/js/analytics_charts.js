// analytics_charts.js
// Requires Chart.js v3+ loaded on the page
document.addEventListener('DOMContentLoaded', () => {
    const apiUrl = '/api/analytics_data.php';

    const tabButtons = Array.from(document.querySelectorAll('.tab-btn'));
    const tabPanels = Array.from(document.querySelectorAll('.tab-panel'));

    const charts = {
        flashTotals: null,
        flashTime: null,
        examAvg: null,
        examPass: null,
        studyExam: null
    };

    function safeFetchJson(url) {
        return fetch(url).then((r) => {
            if (!r.ok) {
                throw new Error('Network response not ok');
            }
            return r.json();
        });
    }

    function destroyIfExists(ref) {
        if (ref && typeof ref.destroy === 'function') {
            ref.destroy();
        }
    }

    function setMessage(containerId, message) {
        const el = document.getElementById(containerId);
        if (!el) {
            return;
        }
        el.innerHTML = `<p class="summary-box" style="margin-top:1rem;">${message}</p>`;
    }

    function activateTab(tabId, buttonEl) {
        tabButtons.forEach((btn) => btn.classList.remove('active'));
        tabPanels.forEach((panel) => panel.classList.remove('active'));

        if (buttonEl) {
            buttonEl.classList.add('active');
        }

        const panel = document.getElementById(tabId);
        if (panel) {
            panel.classList.add('active');
        }
    }

    function renderMockExam(rows) {
        const container = document.getElementById('tab-exams-charts');
        if (!container) {
            return;
        }

        if (!Array.isArray(rows) || rows.length === 0) {
            setMessage('tab-exams-charts', 'No mock exam analytics data yet.');
            return;
        }

        container.innerHTML = `
            <h3 style="margin-bottom:1rem;">Mock Exam Analytics</h3>
            <div style="display:grid; grid-template-columns: 1fr; gap:1rem;">
                <div class="card" style="padding:1rem;">
                    <h4>Average Score (by Exam)</h4>
                    <div style="height:420px;"><canvas id="examAvgCanvas" height="420"></canvas></div>
                </div>
                <div class="card" style="padding:1rem;">
                    <h4>Pass Rate (by Exam)</h4>
                    <div style="height:360px;"><canvas id="examPassCanvas" height="360"></canvas></div>
                </div>
            </div>
        `;

        const labels = rows.map((r) => r.exam_name);
        const avgScores = rows.map((r) => Number(r.avg_score || 0));
        const passRates = rows.map((r) => {
            const attempts = Number(r.attempts || 0);
            if (attempts === 0) {
                return 0;
            }
            return Math.round((Number(r.passed_count || 0) / attempts) * 100);
        });

        const ctxAvg = document.getElementById('examAvgCanvas').getContext('2d');
        destroyIfExists(charts.examAvg);
        charts.examAvg = new Chart(ctxAvg, {
            type: 'bar',
            data: {
                labels,
                datasets: [{ label: 'Avg Score (%)', data: avgScores, backgroundColor: '#8b5cf6' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.parsed.y.toFixed(1)}%` } }
                },
                scales: {
                    x: { ticks: { color: '#ddd' } },
                    y: { beginAtZero: true, max: 100, ticks: { color: '#ddd' } }
                }
            }
        });

        const ctxPass = document.getElementById('examPassCanvas').getContext('2d');
        destroyIfExists(charts.examPass);
        charts.examPass = new Chart(ctxPass, {
            type: 'bar',
            data: {
                labels,
                datasets: [{ label: 'Pass Rate (%)', data: passRates, backgroundColor: '#06b6d4' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { color: '#ddd' } },
                    x: { ticks: { color: '#ddd' } }
                }
            }
        });
    }

    function renderFlashcardTotals(payload) {
        const container = document.getElementById('tab-flashcards-charts');
        if (!container) {
            return;
        }

        const totals = Array.isArray(payload.flashcard_totals) ? payload.flashcard_totals : [];
        const timeSeries = Array.isArray(payload.flashcard_timeseries) ? payload.flashcard_timeseries : [];

        const totalsWithSessions = totals.filter((r) => Number(r.sessions || 0) > 0);

        if (totalsWithSessions.length === 0) {
            setMessage('tab-flashcards-charts', 'No flashcard sessions recorded yet. Open a flashcard set and flip at least one card.');
            return;
        }

        container.innerHTML = `
            <h3 style="margin-bottom:1rem;">Flashcard Analytics</h3>
            <div style="display:grid; grid-template-columns: 1fr; gap:1rem;">
                <div class="card" style="padding:1rem;">
                    <h4>Sessions by Exam</h4>
                    <div style="height:420px;"><canvas id="flashTotalsCanvas" height="420"></canvas></div>
                </div>
                <div class="card" style="padding:1rem;">
                    <h4>Sessions Over Time (last 30 days)</h4>
                    <div style="height:360px;"><canvas id="flashTimeCanvas" height="360"></canvas></div>
                </div>
            </div>
        `;

        const labels = totalsWithSessions.map((r) => r.exam_name);
        const sessions = totalsWithSessions.map((r) => Number(r.sessions || 0));

        const ctx = document.getElementById('flashTotalsCanvas').getContext('2d');
        destroyIfExists(charts.flashTotals);

        // If we only have a few exams, a pie/doughnut gives a clearer distribution
        const usePie = labels.length <= 6;
        if (usePie) {
            // build a palette for slices
            const palette = labels.map((_, i) => {
                const hues = [160, 200, 260, 120, 30, 340];
                return `hsl(${hues[i % hues.length]} 70% 50%)`;
            });

            charts.flashTotals = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{ data: sessions, backgroundColor: palette }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right', labels: { color: '#fff' } } }
                }
            });
        } else {
            charts.flashTotals = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [ { label: 'Sessions', data: sessions, backgroundColor: '#10b981' } ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { color: '#fff' } } },
                    scales: {
                        x: { ticks: { color: '#ddd' } },
                        y: { beginAtZero: true, ticks: { color: '#ddd' } }
                    }
                }
            });
        }

        const tsLabels = timeSeries.map((r) => r.day);
        const tsSessions = timeSeries.map((r) => Number(r.sessions || 0));

        const ctx2 = document.getElementById('flashTimeCanvas').getContext('2d');
        destroyIfExists(charts.flashTime);
        charts.flashTime = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: tsLabels,
                datasets: [
                    {
                        label: 'Sessions',
                        data: tsSessions,
                        backgroundColor: '#06b6d4'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#fff' } } },
                scales: {
                    x: { ticks: { color: '#ddd' } },
                    y: { beginAtZero: true, ticks: { color: '#ddd' } }
                }
            }
        });
    }

    function renderStudyStats(rows) {
        const container = document.getElementById('tab-materials-charts');
        if (!container) {
            return;
        }

        if (!Array.isArray(rows) || rows.length === 0) {
            setMessage('tab-materials-charts', 'No study material analytics data yet.');
            return;
        }

        container.innerHTML = `
            <h3 style="margin-bottom:1rem;">Study Material Analytics</h3>
            <div class="card" style="padding:1rem;">
                <h4>Materials by Type / Exam</h4>
                <div style="height:420px;"><canvas id="studyCanvas" height="420"></canvas></div>
            </div>
        `;

        const labels = rows.map((r) => `${r.exam_name} (${r.content_type})`);
        const counts = rows.map((r) => Number(r.count || 0));

        const ctx = document.getElementById('studyCanvas').getContext('2d');
        destroyIfExists(charts.studyExam);
        charts.studyExam = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{ label: 'Materials', data: counts, backgroundColor: '#06b6d4' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { color: '#ddd' } },
                    y: { ticks: { color: '#ddd' } }
                }
            }
        });
    }

    function loadAll() {
        if (typeof Chart === 'undefined') {
            setMessage('tab-exams-charts', 'Chart.js failed to load.');
            setMessage('tab-flashcards-charts', 'Chart.js failed to load.');
            setMessage('tab-materials-charts', 'Chart.js failed to load.');
            return;
        }

        safeFetchJson(apiUrl)
            .then((data) => {
                if (!data) {
                    setMessage('tab-exams-charts', 'No analytics data available.');
                    setMessage('tab-flashcards-charts', 'No analytics data available.');
                    setMessage('tab-materials-charts', 'No analytics data available.');
                    return;
                }

                renderMockExam(data.mock_exam_stats || []);
                renderFlashcardTotals({
                    flashcard_totals: data.flashcard_totals || [],
                    flashcard_timeseries: data.flashcard_timeseries || []
                });
                renderStudyStats(data.study_stats || []);
            })
            .catch((err) => {
                console.error('Analytics load error:', err);
                setMessage('tab-exams-charts', 'Failed to load mock exam analytics.');
                setMessage('tab-flashcards-charts', 'Failed to load flashcard analytics.');
                setMessage('tab-materials-charts', 'Failed to load study material analytics.');
            });
    }

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            activateTab(btn.dataset.tab, btn);
        });
    });

    if (tabButtons.length > 0 && !document.querySelector('.tab-btn.active')) {
        activateTab(tabButtons[0].dataset.tab, tabButtons[0]);
    }

    loadAll();
});
