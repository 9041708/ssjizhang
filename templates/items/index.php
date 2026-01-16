<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-1">项目管理</h2>
        <div class="small text-muted">项目隶属于具体分类，用于进一步细分支出 / 收入明细。</div>
    </div>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalItemCreate">新增项目</button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- 新增项目弹窗 -->
<div class="modal fade" id="modalItemCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增项目</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="row g-2 align-items-end" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="col-12">
                        <label class="form-label small">所属分类</label>
                        <select name="category_id" class="form-select form-select-sm" required>
                            <option value="">请选择分类</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars('[' . ($c['type'] === 'expense' ? '支出' : '收入') . '] ' . $c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">项目名称</label>
                        <input type="text" name="name" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">项目图标</label>
                        <div class="form-text small mb-1">可选择上传小图标，或从图标库中复用已有图标（可选）。</div>
                        <div class="btn-group btn-group-sm mb-2" role="group">
                            <input type="radio" class="btn-check" name="icon_mode" id="createItemIconNone" value="none" checked>
                            <label class="btn btn-outline-secondary" for="createItemIconNone">不使用图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="createItemIconFile" value="file">
                            <label class="btn btn-outline-secondary" for="createItemIconFile">上传图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="createItemIconLib" value="library">
                            <label class="btn btn-outline-secondary" for="createItemIconLib">从图标库选择</label>
                        </div>
                        <div class="icon-input-file d-none mb-2">
                            <input type="file" name="icon_file" accept="image/*" class="form-control form-control-sm">
                            <div class="form-text small">建议使用正方形 PNG，小于 512KB。</div>
                        </div>
                        <div class="icon-input-library d-none mb-2">
                            <?php if (!empty($iconLibrary)): ?>
                                <select name="icon_library_id" class="form-select form-select-sm icon-library-select">
                                    <option value="">请选择图标（图标库）</option>
                                    <?php foreach ($iconLibrary as $lib): ?>
                                        <option value="<?= (int)$lib['id'] ?>" data-file-path="<?= htmlspecialchars($lib['file_path'] ?? '', ENT_QUOTES) ?>">
                                            <?= htmlspecialchars($lib['name'] ?? ('图标 #' . (int)$lib['id'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-2 small icon-library-preview text-muted"></div>
                            <?php else: ?>
                                <div class="form-text small text-muted">当前图标库中暂无图标，可在图标库页面添加常用图标。</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">排序</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" value="0">
                    </div>
                    <div class="col-6 d-grid">
                        <button type="submit" class="btn btn-sm btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="h6 mb-3">项目列表</h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 table-accounts">
                <thead class="table-light">
                <tr>
                    <th>所属分类</th>
                    <th style="width:80px;">图标</th>
                    <th>项目名称</th>
                    <th style="width:90px;">排序</th>
                    <th class="text-center" style="width:180px;">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="4" class="text-center text-muted small">暂无项目</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $i): ?>
                        <tr>
                            <td>
                                <?php
                                $catLabel = '';
                                foreach ($categories as $c) {
                                    if ((int)$c['id'] === (int)$i['category_id']) {
                                        $catLabel = '[' . ($c['type'] === 'expense' ? '支出' : '收入') . '] ' . $c['name'];
                                        break;
                                    }
                                }
                                ?>
                                <?= htmlspecialchars($catLabel) ?>
                            </td>
                            <td>
                                <?php if (!empty($i['icon_type']) && !empty($i['icon_value'])): ?>
                                    <?php if ($i['icon_type'] === 'file'): ?>
                                        <img src="/uploads/<?= htmlspecialchars($i['icon_value']) ?>" alt="图标" class="rounded" style="width:24px;height:24px;object-fit:cover;">
                                    <?php elseif ($i['icon_type'] === 'svg'): ?>
                                        <span class="item-icon d-inline-block" style="width:24px;height:24px;overflow:hidden;">
                                            <?= $i['icon_value'] ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($i['name']) ?></td>
                            <td><?= (int)$i['sort_order'] ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalItemEdit"
                                            data-id="<?= (int)$i['id'] ?>"
                                            data-category-id="<?= (int)$i['category_id'] ?>"
                                            data-name="<?= htmlspecialchars($i['name'], ENT_QUOTES) ?>"
                                            data-sort="<?= (int)$i['sort_order'] ?>"
                                            data-icon-type="<?= htmlspecialchars($i['icon_type'] ?? '', ENT_QUOTES) ?>"
                                            data-icon-value="<?= htmlspecialchars($i['icon_value'] ?? '', ENT_QUOTES) ?>">
                                        编辑
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('确定删除该项目吗？已有记账数据的项目无法删除。');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 编辑项目弹窗 -->
<div class="modal fade" id="modalItemEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑项目</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="row g-2 align-items-end" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editItemId">
                    <div class="col-12">
                        <label class="form-label small">所属分类</label>
                        <select name="category_id" id="editItemCategory" class="form-select form-select-sm" required>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars('[' . ($c['type'] === 'expense' ? '支出' : '收入') . '] ' . $c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">项目名称</label>
                        <input type="text" name="name" id="editItemName" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">项目图标</label>
                        <div class="form-text small mb-1">可保留原图标，或重新上传 / 从图标库选择 / 清除图标。</div>
                        <div class="btn-group btn-group-sm mb-2" role="group">
                            <input type="radio" class="btn-check" name="icon_mode" id="editItemIconKeep" value="none" checked>
                            <label class="btn btn-outline-secondary" for="editItemIconKeep">保持不变</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="editItemIconFile" value="file">
                            <label class="btn btn-outline-secondary" for="editItemIconFile">上传新图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="editItemIconLib" value="library">
                            <label class="btn btn-outline-secondary" for="editItemIconLib">从图标库选择</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="editItemIconClear" value="clear">
                            <label class="btn btn-outline-danger" for="editItemIconClear">清除图标</label>
                        </div>
                        <div class="icon-input-file d-none mb-2">
                            <input type="file" name="icon_file" accept="image/*" class="form-control form-control-sm">
                        </div>
                        <div class="icon-input-library d-none mb-2">
                            <?php if (!empty($iconLibrary)): ?>
                                <select name="icon_library_id" class="form-select form-select-sm icon-library-select">
                                    <option value="">请选择图标（图标库）</option>
                                    <?php foreach ($iconLibrary as $lib): ?>
                                        <option value="<?= (int)$lib['id'] ?>" data-file-path="<?= htmlspecialchars($lib['file_path'] ?? '', ENT_QUOTES) ?>">
                                            <?= htmlspecialchars($lib['name'] ?? ('图标 #' . (int)$lib['id'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-2 small icon-library-preview text-muted"></div>
                            <?php else: ?>
                                <div class="form-text small text-muted">当前图标库中暂无图标，可在图标库页面添加常用图标。</div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2 small" id="editItemIconCurrentPreview"></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small">排序</label>
                        <input type="number" name="sort_order" id="editItemSort" class="form-control form-control-sm" value="0">
                    </div>
                    <div class="col-12 col-md-6 d-flex justify-content-end align-items-end gap-2 mt-2 mt-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-sm btn-primary">保存修改</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('modalItemEdit');
    if (!modalEl) return;

    modalEl.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;

        var id = button.getAttribute('data-id');
        var categoryId = button.getAttribute('data-category-id');
        var name = button.getAttribute('data-name') || '';
        var sort = button.getAttribute('data-sort') || '0';
        var iconType = button.getAttribute('data-icon-type') || '';
        var iconValue = button.getAttribute('data-icon-value') || '';

        var idInput = document.getElementById('editItemId');
        var categorySelect = document.getElementById('editItemCategory');
        var nameInput = document.getElementById('editItemName');
        var sortInput = document.getElementById('editItemSort');
        var iconPreview = document.getElementById('editItemIconCurrentPreview');

        if (idInput) idInput.value = id;
        if (nameInput) nameInput.value = name;
        if (sortInput) sortInput.value = sort;
        if (categorySelect && categoryId) {
            categorySelect.value = categoryId;
        }

        if (iconPreview) {
            if (iconType === 'file' && iconValue) {
                iconPreview.innerHTML = '<span class="me-2">当前图标：</span><img src="/uploads/' + iconValue + '" alt="图标" style="width:24px;height:24px;object-fit:cover;" class="rounded">';
            } else if (iconType === 'svg' && iconValue) {
                iconPreview.innerHTML = '<span class="me-2">当前图标：</span><span class="item-icon" style="width:24px;height:24px;display:inline-block;overflow:hidden;vertical-align:middle;">' + iconValue + '</span>';
            } else {
                iconPreview.innerHTML = '<span class="text-muted">当前无图标</span>';
            }
        }

    });

    function bindIconMode(container) {
        if (!container) return;
        var fileBlock = container.querySelector('.icon-input-file');
        var libBlock = container.querySelector('.icon-input-library');
        var radios = container.querySelectorAll('input[name="icon_mode"]');
        function update(mode) {
            if (fileBlock) fileBlock.classList.toggle('d-none', mode !== 'file');
            if (libBlock) libBlock.classList.toggle('d-none', mode !== 'library');
        }
        radios.forEach(function (r) {
            r.addEventListener('change', function () {
                update(this.value);
            });
            if (r.checked) {
                update(r.value);
            }
        });
    }

    bindIconMode(document.querySelector('#modalItemCreate .modal-body'));
    bindIconMode(document.querySelector('#modalItemEdit .modal-body'));

    function bindIconLibrary(container) {
        if (!container) return;
        var select = container.querySelector('.icon-library-select');
        var preview = container.querySelector('.icon-library-preview');
        if (!select || !preview) return;

        function updatePreview() {
            var option = select.options[select.selectedIndex];
            if (!option || !option.value) {
                preview.innerHTML = '<span class="text-muted">未选择图标</span>';
                return;
            }
            var path = option.getAttribute('data-file-path') || '';
            if (path) {
                preview.innerHTML = '<span class="me-2">预览：</span><img src="/uploads/' + path + '" alt="图标预览" style="width:24px;height:24px;object-fit:cover;" class="rounded">';
            } else {
                preview.innerHTML = '<span class="text-muted">未选择图标</span>';
            }
        }

        select.addEventListener('change', updatePreview);
        updatePreview();
    }

    bindIconLibrary(document.querySelector('#modalItemCreate .modal-body'));
    bindIconLibrary(document.querySelector('#modalItemEdit .modal-body'));
});
</script>
