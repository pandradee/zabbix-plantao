<?php declare(strict_types = 1);

/**
 * @var CView  $this
 * @var array  $data
 */
?>
<style>
.phn-wrap { padding: 20px 0; color: #f2f2f2; }

.phn-badge-ok {
    background: #162616; color: #59db8f;
    padding: 2px 8px; border-radius: 3px;
    font-size: 11px; font-weight: 600;
    border: 1px solid #2a5a2a;
}
.phn-badge-no {
    background: #252525; color: #666;
    padding: 2px 8px; border-radius: 3px;
    font-size: 11px; border: 1px solid #3a3a3a;
}

output .btn-overlay-close { position: absolute; top: 6px; right: 6px; }
.phn-form-inline { display: flex; gap: 6px; align-items: center; }
</style>

<?php ob_start(); ?>

<?php if ($data['success']): ?>
<output class="msg-good" role="contentinfo">
    <span><?= htmlspecialchars($data['success']) ?></span>
    <button class="btn-overlay-close" type="button" title="Close" onclick="this.parentElement.remove()"></button>
</output>
<?php endif; ?>
<?php if ($data['error']): ?>
<output class="msg-bad" role="contentinfo">
    <span><?= htmlspecialchars($data['error']) ?></span>
    <button class="btn-overlay-close" type="button" title="Close" onclick="this.parentElement.remove()"></button>
</output>
<?php endif; ?>


<table class="list-table">
    <thead>
        <tr>
            <th>Usuario</th>
            <th>Nome</th>
            <th>Telefone Atual</th>
            <th>Atualizar Telefone</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($data['users'] as $u): ?>
    <tr>
        <td class="overflow-ellipsis"><?= htmlspecialchars($u['username']) ?></td>
        <td class="overflow-ellipsis"><?= htmlspecialchars($u['name'] . ' ' . $u['surname']) ?></td>
        <td>
            <?php if ($u['phone']): ?>
                <span class="phn-badge-ok"><?= htmlspecialchars($u['phone']) ?></span>
            <?php else: ?>
                <span class="phn-badge-no">nao cadastrado</span>
            <?php endif; ?>
        </td>
        <td>
            <form method="post" action="zabbix.php?action=phones.save" class="phn-form-inline">
                <input type="hidden" name="userid" value="<?= (int)$u['userid'] ?>">
                <input type="text" name="phone"
                    value="<?= htmlspecialchars($u['phone']) ?>"
                    placeholder="+5511999999999">
                <button class="btn" type="submit">Salvar</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
$page_content = ob_get_clean();

$raw = new class($page_content) {
    public function __construct(private string $html) {}
    public function toString(bool $destroy = true): string { return $this->html; }
};

(new CHtmlPage())
    ->setTitle('Telefones de Plantão')
    ->addItem($raw)
    ->show();
