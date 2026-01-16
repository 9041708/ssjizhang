<?php
use App\Model\Feedback;

$categories = $categories ?? Feedback::categoryLabels();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-0">问题反馈 &amp; FAQ</h2>
        <div class="small text-muted">提交问题或建议，查看历史问题与解决方案。</div>
    </div>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#feedbackCreateModal">
        我要反馈
    </button>
    
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger small mb-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success small mb-2"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- 提交反馈弹窗 -->
<div class="modal fade" id="feedbackCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">提交问题反馈</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="action" value="create">
                    <div class="col-12 col-md-4">
                        <label class="form-label small">反馈类型</label>
                        <select name="category" class="form-select form-select-sm">
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $key === Feedback::CATEGORY_SUGGEST ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">问题描述</label>
                        <textarea name="content" rows="4" class="form-control form-control-sm" placeholder="请尽量详细描述你遇到的问题、期望的功能或使用场景，便于快速定位和处理。"></textarea>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label small">相关截图（可多选）</label>
                        <input type="file" name="images[]" multiple accept="image/*" class="form-control form-control-sm">
                        <div class="form-text small">可一次选择多张图片，单张大小不超过 10MB，主要用于错误截图或场景说明。</div>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-sm btn-primary">提交反馈</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="h6 mb-0">历史问题 &amp; 常见问答</h3>
            <div class="input-group input-group-sm" style="max-width:260px;">
                <span class="input-group-text">搜索</span>
                <input type="text" id="feedbackSearchInput" class="form-control" placeholder="按问题 / 回复 / 用户模糊搜索">
            </div>
        </div>
        <?php if (empty($feedbackList)): ?>
            <div class="text-muted small">当前还没有任何反馈记录，欢迎在上方提交你的第一个建议或问题。</div>
        <?php else: ?>
            <div class="accordion" id="feedbackFaqAccordion">
                <?php foreach ($feedbackList as $row): ?>
                    <?php
                    $id = (int)$row['id'];
                    $catKey = (string)($row['category'] ?? '');
                    $catLabel = $categories[$catKey] ?? '其它反馈';
                    $status = (string)($row['status'] ?? 'pending');
                    $nickname = $row['nickname'] ?? ($row['username'] ?? '用户');
                    $createdAt = $row['created_at'] ?? '';
                    $hasReply = !empty($row['admin_reply']);
                    $statusLabel = $status === 'resolved' ? '已解决' : ($status === 'closed' ? '已关闭' : '处理中');
                    $statusClass = $status === 'resolved' ? 'bg-success' : ($status === 'closed' ? 'bg-secondary' : 'bg-warning text-dark');
                    ?>
                    <div class="accordion-item mb-1" data-feedback-search-text="<?= htmlspecialchars(($row['content'] ?? '') . ' ' . ($row['admin_reply'] ?? '') . ' ' . ($nickname ?? ''), ENT_QUOTES) ?>">
                        <h2 class="accordion-header" id="fbHeading<?= $id ?>">
                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#fbCollapse<?= $id ?>" aria-expanded="false" aria-controls="fbCollapse<?= $id ?>">
                                <span class="badge rounded-pill me-2 bg-light text-muted border small"><?= htmlspecialchars($catLabel) ?></span>
                                <span class="me-2 small text-muted">来自 <?= htmlspecialchars($nickname) ?></span>
                                <span class="me-2 small text-muted"><?= htmlspecialchars($createdAt) ?></span>
                                <span class="badge ms-auto <?= $statusClass ?> small"><?= htmlspecialchars($statusLabel) ?></span>
                            </button>
                        </h2>
                        <div id="fbCollapse<?= $id ?>" class="accordion-collapse collapse" aria-labelledby="fbHeading<?= $id ?>" data-bs-parent="#feedbackFaqAccordion">
                            <div class="accordion-body small">
                                <div class="mb-2">
                                    <div class="fw-semibold mb-1">用户问题 / 建议：</div>
                                    <div class="text-body-secondary" style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($row['content'] ?? '')) ?></div>
                                </div>
                                <?php if (!empty($row['images_array'])): ?>
                                    <div class="mb-2">
                                        <div class="fw-semibold mb-1">相关截图：</div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($row['images_array'] as $img): ?>
                                                <img src="/uploads/<?= htmlspecialchars($img, ENT_QUOTES) ?>"
                                                     alt="反馈截图"
                                                     class="attachment-thumb"
                                                     style="width:72px;height:72px;object-fit:cover;border-radius:4px;cursor:zoom-in;"
                                                     data-attachment-preview="/uploads/<?= htmlspecialchars($img, ENT_QUOTES) ?>">
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-2 pt-2 border-top">
                                    <div class="fw-semibold mb-1">系统回复：</div>
                                    <?php if ($hasReply): ?>
                                        <div class="text-success" style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($row['admin_reply'] ?? '')) ?></div>
                                        <?php if (!empty($row['admin_reply_at'])): ?>
                                            <div class="small text-muted mt-1">回复时间：<?= htmlspecialchars($row['admin_reply_at']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-muted">暂未回复，管理员处理后会在此展示具体解决方案，可稍后再来查看。</div>
                                    <?php endif; ?>
                                    <?php if (!empty($isAdmin) && $isAdmin): ?>
                                        <form method="post" enctype="multipart/form-data" class="mt-3 border-top pt-2 small">
                                            <input type="hidden" name="action" value="reply">
                                            <input type="hidden" name="id" value="<?= $id ?>">
                                            <div class="mb-2">
                                                <label class="form-label small mb-1">编辑系统回复</label>
                                                <textarea name="reply" class="form-control form-control-sm" rows="3" placeholder="填写给用户的解决方案或说明"><?= htmlspecialchars($row['admin_reply'] ?? '') ?></textarea>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small mb-1">附加图片（可选，多张）</label>
                                                <input type="file" name="reply_images[]" class="form-control form-control-sm" accept="image/*" multiple>
                                                <div class="form-text small">上传的图片会与用户原有截图一并展示在小程序和此处详情中。</div>
                                            </div>
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <div class="small">状态：</div>
                                                <select name="status" class="form-select form-select-sm w-auto">
                                                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>处理中</option>
                                                    <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>已解决</option>
                                                    <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>已关闭</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-primary ms-auto">保存回复</button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('feedbackSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var keyword = (searchInput.value || '').toLowerCase().trim();
            var items = document.querySelectorAll('#feedbackFaqAccordion .accordion-item');
            items.forEach(function (item) {
                var text = (item.getAttribute('data-feedback-search-text') || '').toLowerCase();
                if (!keyword || text.indexOf(keyword) !== -1) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>
