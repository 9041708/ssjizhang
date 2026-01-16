<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-1">图标库管理</h2>
        <div class="small text-muted">集中管理常用的小图标，分类 / 项目 / 账户等页面可直接从图标库中复用，避免重复上传。</div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div class="mb-2 mb-md-0">
            <h3 class="h6 mb-1">从现有数据初始化图标库</h3>
            <div class="small text-muted">扫描当前用户的分类 / 项目 / 账户中已上传的文件图标，一键写入图标库（已存在路径会自动跳过）。</div>
        </div>
        <div>
            <form method="post" onsubmit="return confirm('确定要扫描并导入现有分类/项目/账户中的文件图标吗？已存在的会自动跳过。');">
                <input type="hidden" name="action" value="init_from_existing">
                <button type="submit" class="btn btn-sm btn-outline-primary">一键导入历史图标</button>
            </form>
        </div>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h6 mb-0">图标库列表</h3>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#iconCreateModal">新增图标</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width:80px;">预览</th>
                    <th>名称</th>
                    <th class="text-muted small" style="width:220px;">存储路径（相对于 /uploads）</th>
                    <th class="text-center" style="width:140px;">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($icons)): ?>
                    <tr><td colspan="4" class="text-center text-muted small">图标库中暂无图标，可在上方表单中添加。</td></tr>
                <?php else: ?>
                    <?php foreach ($icons as $icon): ?>
                        <tr>
                            <td>
                                <img src="/uploads/<?= htmlspecialchars($icon['file_path'] ?? '') ?>" alt="图标预览" class="rounded" style="width:24px;height:24px;object-fit:cover;">
                            </td>
                            <td><?= htmlspecialchars($icon['name'] ?? '') ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($icon['file_path'] ?? '') ?></td>
                            <td class="text-center">
                                <form method="post" class="d-inline" onsubmit="return confirm('确定从图标库中删除该记录吗？不会删除实际图片文件。');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$icon['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">删除记录</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 新增图标弹窗 -->
<div class="modal fade" id="iconCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增图标到图标库</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label small">图标名称</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="例如：微信、支付宝、工资" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small d-block">上传图标文件</label>
                        <input type="file" name="icon_file" accept="image/*" class="form-control form-control-sm" required>
                        <div class="form-text small">建议使用正方形 PNG/JPG，小于 512KB。</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-sm btn-primary">添加到图标库</button>
                </div>
            </form>
        </div>
    </div>
    </div>
