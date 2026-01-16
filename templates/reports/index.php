<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">统计报表</h2>
    <div class="small text-muted">按时间维度统计收入 / 支出，并以柱状图展示趋势。</div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get">
            <input type="hidden" name="route" value="reports">
            <div class="col-6 col-md-2 report-filter-year-mode">
                <label class="form-label small">时间模式</label>
                <select name="mode" class="form-select form-select-sm">
                    <option value="year" <?= $mode === 'year' ? 'selected' : '' ?>>按年度</option>
                    <option value="quarter" <?= $mode === 'quarter' ? 'selected' : '' ?>>按季度</option>
                    <option value="month" <?= $mode === 'month' ? 'selected' : '' ?>>按月度</option>
                    <option value="day" <?= $mode === 'day' ? 'selected' : '' ?>>今日</option>
                    <option value="yesterday" <?= $mode === 'yesterday' ? 'selected' : '' ?>>昨日</option>
                    <option value="custom" <?= $mode === 'custom' ? 'selected' : '' ?>>自定义</option>
                </select>
            </div>
            <div class="col-6 col-md-2 report-filter-year">
                <label class="form-label small">年份</label>
                <input type="number" name="year" class="form-control form-control-sm" value="<?= (int)$year ?>">
            </div>
            <div class="col-6 col-md-2 report-filter-month">
                <label class="form-label small">月份</label>
                <input type="number" name="month" class="form-control form-control-sm" value="<?= (int)$month ?>" min="1" max="12">
            </div>
            <div class="col-6 col-md-2 report-filter-quarter">
                <label class="form-label small">季度</label>
                <select name="quarter" class="form-select form-select-sm">
                    <option value="1" <?= (int)$quarter === 1 ? 'selected' : '' ?>>第一季度</option>
                    <option value="2" <?= (int)$quarter === 2 ? 'selected' : '' ?>>第二季度</option>
                    <option value="3" <?= (int)$quarter === 3 ? 'selected' : '' ?>>第三季度</option>
                    <option value="4" <?= (int)$quarter === 4 ? 'selected' : '' ?>>第四季度</option>
                </select>
            </div>
            <div class="col-6 col-md-2 report-filter-date-from">
                <label class="form-label small">开始日期</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="col-6 col-md-2 report-filter-date-to">
                <label class="form-label small">结束日期</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-sm btn-primary">统计</button>
            </div>
        </form>
        <div class="small text-muted mt-2">
            提示：当选择“当天 / 昨日”时，年月和自定义日期会被当前日期覆盖；“自定义区间”时优先使用开始 / 结束日期。
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">总收入</div>
                <div class="fs-4 fw-semibold text-danger">¥ <?= number_format($totalIncome, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">总支出</div>
                <div class="fs-4 fw-semibold text-success">¥ <?= number_format($totalExpense, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">结余（收入 - 支出）</div>
                <div class="fs-4 fw-semibold <?= $totalIncome - $totalExpense >= 0 ? 'text-primary' : 'text-danger' ?>">
                    ¥ <?= number_format($totalIncome - $totalExpense, 2) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (in_array($mode, ['year','quarter','month'], true)): ?>
<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">期间总预算（支出）</div>
                <div class="fs-4 fw-semibold text-primary">¥ <?= number_format($totalBudgetExpense ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">期间已支出（按预算口径）</div>
                <div class="fs-4 fw-semibold text-danger">¥ <?= number_format($totalUsedExpense ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">预算剩余</div>
                <div class="fs-4 fw-semibold text-success">¥ <?= number_format(($totalBudgetExpense ?? 0) - ($totalUsedExpense ?? 0), 2) ?></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modeSelect = document.querySelector('select[name="mode"]');
    if (!modeSelect) return;

    var yearCol = document.querySelector('.report-filter-year');
    var monthCol = document.querySelector('.report-filter-month');
    var quarterCol = document.querySelector('.report-filter-quarter');
    var dateFromCol = document.querySelector('.report-filter-date-from');
    var dateToCol = document.querySelector('.report-filter-date-to');

    var dateFromInput = document.querySelector('input[name="date_from"]');
    var dateToInput = document.querySelector('input[name="date_to"]');

    function toggle(el, show) {
        if (!el) return;
        el.classList.toggle('d-none', !show);
    }

    function formatDate(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function applyQuickDate(mode) {
        if (!dateFromInput || !dateToInput) return;
        var today = new Date();
        if (mode === 'day') {
            var d = formatDate(today);
            dateFromInput.value = d;
            dateToInput.value = d;
        } else if (mode === 'yesterday') {
            var yDay = new Date(today.getTime());
            yDay.setDate(yDay.getDate() - 1);
            var yd = formatDate(yDay);
            dateFromInput.value = yd;
            dateToInput.value = yd;
        }
    }

    function updateVisibility() {
        var mode = modeSelect.value;

        // 年份：按年度 / 按季度 / 按月度
        var showYear = (mode === 'year' || mode === 'quarter' || mode === 'month');
        toggle(yearCol, showYear);

        // 月份：仅按月度
        toggle(monthCol, mode === 'month');

        // 季度：仅按季度
        toggle(quarterCol, mode === 'quarter');

        // 日期范围：今日 / 昨日 / 自定义
        var showDate = (mode === 'day' || mode === 'yesterday' || mode === 'custom');
        toggle(dateFromCol, showDate);
        toggle(dateToCol, showDate);

        // 切换到“今日 / 昨日”时自动设置日期
        if (mode === 'day' || mode === 'yesterday') {
            applyQuickDate(mode);
        }
    }

    modeSelect.addEventListener('change', updateVisibility);
    updateVisibility();
});
</script>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="h6 mb-3">收支柱状图</h3>
        <?php if (empty($labels)): ?>
            <div class="text-muted small">当前时间范围内暂无记账数据。</div>
        <?php else: ?>
            <canvas id="reportChart" style="max-height:380px;"></canvas>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($labels)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function() {
        const ctx = document.getElementById('reportChart');
        if (!ctx) return;
        const labels = <?= json_encode(array_values($labels), JSON_UNESCAPED_UNICODE) ?>;
        const incomeData = <?= json_encode(array_map('floatval', $incomeData), JSON_UNESCAPED_UNICODE) ?>;
        const expenseData = <?= json_encode(array_map('floatval', $expenseData), JSON_UNESCAPED_UNICODE) ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '收入',
                        data: incomeData,
                        backgroundColor: 'rgba(220, 53, 69, 0.5)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1
                    },
                    {
                        label: '支出',
                        data: expenseData,
                        backgroundColor: 'rgba(25, 135, 84, 0.5)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ¥ ' + Number(ctx.parsed.y).toFixed(2); } } }
                },
                scales: {
                    x: { stacked: false },
                    y: { beginAtZero: true }
                }
            }
        });
    })();
</script>
<?php endif; ?>

<?php if (!empty($categoryLabels ?? [])): ?>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
        <h3 class="h6 mb-3">按分类 / 项目支出</h3>
        <canvas id="categoryChart" style="max-height:380px;"></canvas>
        <div class="small text-muted mt-2">点击柱子可跳转到对应分类 / 项目的流水列表，并自动带上当前时间范围筛选。</div>
    </div>
</div>

<script>
    (function() {
        const ctx = document.getElementById('categoryChart');
        if (!ctx) return;

        const labels = <?= json_encode(array_values($categoryLabels), JSON_UNESCAPED_UNICODE) ?>;
        const data = <?= json_encode(array_map('floatval', $categoryData), JSON_UNESCAPED_UNICODE) ?>;
        const links = <?= json_encode(array_values($categoryLinks), JSON_UNESCAPED_UNICODE) ?>;

        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '支出',
                        data: data,
                        backgroundColor: 'rgba(25, 135, 84, 0.5)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return '支出: ¥ ' + Number(ctx.parsed.y).toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { maxRotation: 45, minRotation: 0 } },
                    y: { beginAtZero: true }
                },
                onClick: function(evt, elements) {
                    if (!elements || !elements.length) return;
                    const index = elements[0].index;
                    const url = links[index] || null;
                    if (url) {
                        window.location.href = url;
                    }
                }
            }
        });
    })();
</script>
<?php endif; ?>
