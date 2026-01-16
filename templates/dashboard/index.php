<div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm h-100 border-0 dashboard-balance-card" data-group="financial" data-title="金融账户余额">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">金融账户余额</div>
                        <div class="fs-4 fw-semibold">¥ <?= number_format($balances['financial'] ?? 0, 2) ?></div>
                    </div>
                    <div class="text-primary fs-3">💰</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm h-100 border-0 dashboard-balance-card" data-group="saving" data-title="储蓄账户余额">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">储蓄账户余额</div>
                        <div class="fs-4 fw-semibold">¥ <?= number_format($balances['saving'] ?? 0, 2) ?></div>
                    </div>
                    <div class="text-success fs-3">🏦</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm h-100 border-0 dashboard-balance-card" data-group="receivable" data-title="应收账款余额">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">应收账款余额</div>
                        <div class="fs-4 fw-semibold text-info">¥ <?= number_format($balances['receivable'] ?? 0, 2) ?></div>
                    </div>
                    <div class="text-info fs-3">🧾</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm h-100 border-0 dashboard-balance-card" data-group="debt" data-title="应付账款余额">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">应付账款余额</div>
                        <div class="fs-4 fw-semibold text-danger">¥ <?= number_format($balances['debt'] ?? 0, 2) ?></div>
                    </div>
                    <div class="text-danger fs-3">📉</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm h-100 border-0 dashboard-balance-card" data-group="other" data-title="其它账户余额">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">其它账户余额</div>
                        <div class="fs-4 fw-semibold">¥ <?= number_format($balances['other'] ?? 0, 2) ?></div>
                    </div>
                    <div class="text-secondary fs-3">📦</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 账户明细弹窗 -->
<div class="modal fade" id="dashboardAccountDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dashboardAccountDetailTitle">账户明细</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                $groupLabels = [
                    'financial'  => '金融账户',
                    'saving'     => '储蓄账户',
                    'receivable' => '应收账款',
                    'debt'       => '应付账款',
                    'other'      => '其它账户',
                ];
                ?>
                <?php foreach ($groupLabels as $code => $label): ?>
                    <div class="dashboard-account-detail d-none" data-group="<?= htmlspecialchars($code, ENT_QUOTES) ?>">
                        <div class="mb-2 small text-muted"><?= htmlspecialchars($label) ?>明细</div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 table-accounts">
                                <thead class="table-light">
                                <tr>
                                    <th>账户名称</th>
                                    <th style="width:80px;">图标</th>
                                    <th>账号 / 卡号</th>
                                    <th class="text-end">当前余额</th>
                                    <th class="text-center" style="width:110px;">操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $list = $accountsByGroup[$code] ?? []; ?>
                                <?php if (empty($list)): ?>
                                    <tr><td colspan="3" class="text-center text-muted small">该分类下暂无账户</td></tr>
                                <?php else: ?>
                                    <?php foreach ($list as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($a['name']) ?></td>
                                            <td>
                                                <?php if (!empty($a['icon_type']) && !empty($a['icon_value'])): ?>
                                                    <?php if ($a['icon_type'] === 'file'): ?>
                                                        <img src="/uploads/<?= htmlspecialchars($a['icon_value']) ?>" alt="图标" class="rounded" style="width:24px;height:24px;object-fit:cover;">
                                                    <?php elseif ($a['icon_type'] === 'svg'): ?>
                                                        <span class="account-icon d-inline-block" style="width:24px;height:24px;overflow:hidden;">
                                                            <?= $a['icon_value'] ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($a['account_no'] ?? '') ?></td>
                                            <td class="text-end">
                                                <span class="<?= ($a['current_balance'] ?? 0) < 0 ? 'text-danger' : 'text-success' ?>">
                                                    ¥ <?= number_format((float)($a['current_balance'] ?? 0), 2) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="/public/index.php?route=transactions&amp;account_id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-primary">查看明细</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-3">
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="text-muted small">当月预算（支出）</div>
                        <div class="fs-4 fw-semibold text-primary">¥ <?= number_format($monthBudget ?? 0, 2) ?></div>
                    </div>
                    <div class="text-primary fs-3">📅</div>
                </div>
                <?php if (!empty($monthBudget)): ?>
                    <div class="small text-muted mb-1">统计范围：仅包含已设置预算的支出分类/项目。</div>
                    <div class="small mb-1">
                        <span class="text-muted">已用预算：</span>
                        <span class="fw-semibold text-danger">¥ <?= number_format($monthBudgetUsed ?? 0, 2) ?></span>
                        <span class="text-muted ms-2">剩余额度：</span>
                        <span class="fw-semibold <?= ($monthBudgetRemain ?? 0) < 0 ? 'text-danger' : 'text-success' ?>">
                            ¥ <?= number_format(max(0, $monthBudgetRemain ?? 0), 2) ?>
                        </span>
                    </div>
                    <div class="progress" style="height:6px;">
                        <?php
                        $ratePercent = (int)($monthBudgetRatePercent ?? 0);
                        $barClass = 'bg-success';
                        $enableReminder = isset($budgetReminderEnabled) ? (bool)$budgetReminderEnabled : true;
                        if ($enableReminder) {
                            if (!empty($monthBudgetOver)) {
                                $barClass = 'bg-danger';
                            } elseif (!empty($monthBudgetWarn)) {
                                $barClass = 'bg-warning';
                            }
                        }
                        ?>
                        <div class="progress-bar <?= $barClass ?>" role="progressbar" style="width: <?= min(100, max(0, $ratePercent)) ?>%;"></div>
                    </div>
                    <div class="small mt-1">
                        <?php if (!empty($enableReminder) && !empty($monthBudgetOver)): ?>
                            <span class="text-danger">本月预算已超支（约 <?= (int)($monthBudgetRatePercent ?? 0) ?>%）。</span>
                        <?php elseif (!empty($enableReminder) && !empty($monthBudgetWarn)): ?>
                            <span class="text-warning">本月已使用约 <?= (int)($monthBudgetRatePercent ?? 0) ?>% 的预算，接近上限。</span>
                        <?php else: ?>
                            <span class="text-muted">本月已使用约 <?= (int)($monthBudgetRatePercent ?? 0) ?>% 的预算。</span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="small text-muted">当前尚未设置当月预算，建议前往“预算管理”页面配置一个整体或分项目预算。</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="text-muted small">当月已支出</div>
                        <div class="fs-4 fw-semibold text-danger">¥ <?= number_format($monthExpense ?? 0, 2) ?></div>
                    </div>
                    <div class="text-danger fs-3">💸</div>
                </div>
                <div class="small text-muted">按“支出”记账合计，方便对比预算执行情况。</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="text-muted small">当月收入 & 结余</div>
                        <div class="fs-6 text-success mb-1">收入：¥ <?= number_format($monthIncome ?? 0, 2) ?></div>
                        <?php $net = $monthNet ?? 0; ?>
                        <div class="fs-6 <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">结余：¥ <?= number_format($net, 2) ?></div>
                    </div>
                    <div class="text-success fs-3">📈</div>
                </div>
                <div class="small text-muted">结余 = 当月收入 - 当月支出，帮助快速了解本月收支情况。</div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($trendLabels7)): ?>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
        <h3 class="h6 mb-3">最近 7 天收支趋势</h3>
        <canvas id="dashboardTrend" style="max-height:320px;"></canvas>
    </div>
</div>

<?php $chartJsLocal = __DIR__ . '/../../assets/vendor/chart/chart.umd.min.js'; if (is_file($chartJsLocal)): ?>
<script src="/assets/vendor/chart/chart.umd.min.js"></script>
<?php else: ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>
<script>
    (function() {
        const ctx = document.getElementById('dashboardTrend');
        if (!ctx) return;
        const labels = <?= json_encode(array_values($trendLabels7), JSON_UNESCAPED_UNICODE) ?>;
        const incomeData = <?= json_encode(array_map('floatval', $trendIncome7), JSON_UNESCAPED_UNICODE) ?>;
        const expenseData = <?= json_encode(array_map('floatval', $trendExpense7), JSON_UNESCAPED_UNICODE) ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '收入',
                        data: incomeData,
                        borderColor: 'rgba(220, 53, 69, 1)',
                        backgroundColor: 'rgba(220, 53, 69, 0.15)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3
                    },
                    {
                        label: '支出',
                        data: expenseData,
                        borderColor: 'rgba(25, 135, 84, 1)',
                        backgroundColor: 'rgba(25, 135, 84, 0.15)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': ¥ ' + Number(ctx.parsed.y).toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: { display: true },
                    y: { beginAtZero: true }
                }
            }
        });
    })();
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var cards = document.querySelectorAll('.dashboard-balance-card');
    var modalEl = document.getElementById('dashboardAccountDetailModal');
    if (!modalEl) return;

    var modalTitleEl = document.getElementById('dashboardAccountDetailTitle');

    function showDetail(group, title) {
        var detailBlocks = modalEl.querySelectorAll('.dashboard-account-detail');
        detailBlocks.forEach(function (el) {
            el.classList.add('d-none');
        });
        var target = modalEl.querySelector('.dashboard-account-detail[data-group="' + group + '"]');
        if (target) {
            target.classList.remove('d-none');
        }
        if (modalTitleEl && title) {
            modalTitleEl.textContent = title;
        }
        if (typeof bootstrap !== 'undefined') {
            var m = bootstrap.Modal.getOrCreateInstance(modalEl);
            m.show();
        }
    }

    cards.forEach(function (card) {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function () {
            var group = card.getAttribute('data-group');
            var title = card.getAttribute('data-title') || '账户明细';
            if (!group) return;
            showDetail(group, title);
        });
    });
});
</script>
