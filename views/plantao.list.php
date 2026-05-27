<?php declare(strict_types = 1);

/**
 * @var CView  $this
 * @var array  $data
 */

$month         = $data['month'];
$year          = $data['year'];
$week_map      = $data['week_map'];
$users         = $data['users'];
$days_in_month = $data['days_in_month'];
$today_str     = date('Y-m-d');

$month_names = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Marco', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];

$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

$prev_url  = 'zabbix.php?action=plantao.list&month=' . $prev_month . '&year=' . $prev_year;
$next_url  = 'zabbix.php?action=plantao.list&month=' . $next_month . '&year=' . $next_year;
$apply_url = 'zabbix.php?action=plantao.apply&month=' . $month . '&year=' . $year;

$first_day_dt  = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$first_weekday = (int) $first_day_dt->format('N') - 1;

function getWeekMonday(int $y, int $m, int $d): string {
    $dt  = new DateTime(sprintf('%04d-%02d-%02d', $y, $m, $d));
    $dow = (int) $dt->format('N');
    if ($dow > 1) $dt->modify('-' . ($dow - 1) . ' days');
    return $dt->format('Y-m-d');
}

ob_start();
?>
<style>
/* ---- Plantao calendar ---- */
.plt-wrap { padding: 0 0 20px; color: #f2f2f2; }
.plt-wrap output { margin: 0 0 16px; }
.plt-nav  { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.plt-nav .plt-month-label { margin: 0; font-size: 15px; font-weight: 600; flex: 1; color: #c8d8e4; }
.plt-nav .btn-icon { flex-shrink: 0; }

.plt-cal { width: 100%; border-collapse: collapse; table-layout: fixed; height: calc(100vh - 340px); min-height: 480px; }
.plt-cal th {
    background: #303030; color: #b0c4d4;
    text-align: center; padding: 8px 4px;
    font-size: 12px; font-weight: 600;
    border: 1px solid #4f4f4f;
}
.plt-cal td {
    border: 1px solid #4f4f4f; vertical-align: top;
    padding: 0; cursor: pointer;
    transition: background 0.1s; height: 1%;
    background: #2b2b2b; position: relative;
    outline: none;
}
.plt-cal td:focus,
.plt-cal td:focus-within,
.plt-cal td:focus-visible  { outline: none !important; box-shadow: none !important; }
.plt-cell-inner {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    overflow: hidden; padding: 7px 8px;
}
a.plt-del:focus, a.plt-del:focus-visible, a.plt-del:active,
a.plt-del:focus-within { outline: none !important; border-bottom: none !important; border: none !important; }
.plt-cal td:hover          { background: #383838; }
.plt-cal td.empty          { background: #222; cursor: default; }
.plt-cal td.today          { background: #2e2200; border-color: #e99003; }
.plt-cal td.today:hover    { background: #3a2b00; }
.plt-cal td.has-tech       { background: #162616; }
.plt-cal td.has-tech:hover { background: #1e3a1e; }
.plt-cal td.past           { opacity: 0.55; }
.plt-cal tbody tr       { height: 1%; }

.plt-day-num { font-size: 13px; font-weight: 700; color: #c8d8e4; }
.plt-today-badge {
    background: #e99003; color: #1a1000;
    border-radius: 3px; padding: 0 5px;
    font-size: 10px; margin-left: 5px;
    font-weight: 700; vertical-align: middle;
}
.plt-tech     { font-size: 11px; color: #59db8f; font-weight: 600; margin-top: 4px; }
.plt-phone    { font-size: 10px; color: #8faabc; margin-top: 2px; }
.plt-no-phone { font-size: 10px; color: #555; font-style: italic; margin-top: 2px; }
.plt-reserva-label { font-size: 9px; color: #666; margin-top: 5px; text-transform: uppercase; letter-spacing: 0.4px; }
.plt-tech-reserva  { font-size: 11px; color: #59db8f; font-weight: 600; margin-top: 1px; }
.plt-phone-reserva { font-size: 10px; color: #8faabc; margin-top: 1px; }
.plt-del {
    display: inline-block; margin-top: 5px;
    font-size: 10px; color: #e45959; text-decoration: none;
}
.plt-del:hover { text-decoration: underline; color: #ff7070; }

/* Form panel */
.plt-form-panel {
    margin-top: 24px; background: #303030;
    border: 1px solid #4f4f4f; border-radius: 4px; padding: 20px 24px;
}
.plt-form-panel h3 { margin: 0 0 18px; font-size: 15px; font-weight: 600; color: #f2f2f2; }
.plt-form-row {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;
}
.plt-form-row label { font-weight: 600; font-size: 13px; color: #9ab; white-space: nowrap; }
.plt-week-info { font-size: 12px; color: #8faabc; font-style: italic; }

/* Tecnico selector row */
.plt-multiselect-wrap {
    flex: 1; min-width: 0; position: relative;
    margin-right: 0 !important;
}
.plt-select-row { display: flex; align-items: stretch; flex: 0 1 300px; min-width: 220px; }
.plt-select-row .multiselect {
    flex: 1; min-width: 0; margin-right: 0 !important;
    border-right: 0 !important;
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
}
.plt-select-row .btn {
    border-top-left-radius: 0; border-bottom-left-radius: 0;
    white-space: nowrap; flex-shrink: 0; align-self: stretch;
}
.plt-ac-dropdown {
    display: none; position: absolute; z-index: 1100;
    top: 100%; left: 0; right: 0;
    background: #2b2b2b; border: 1px solid #4f4f4f;
    border-top: none; max-height: 220px; overflow-y: auto;
}
.plt-ac-dropdown.open { display: block; }
.plt-ac-item {
    padding: 7px 10px; font-size: 13px; color: #f2f2f2;
    cursor: pointer; border-bottom: 1px solid #383838;
}
.plt-ac-item:last-child { border-bottom: none; }
.plt-ac-item:hover, .plt-ac-item.active { background: #505050; }
.plt-ac-item mark { background: none; color: #e99003; font-weight: 700; }

/* output close button position */
output .btn-overlay-close { position: absolute; top: 6px; right: 6px; }

/* Override modal z-index to sit above overlay-bg */
#plt-modal { z-index: 1001 !important; }
</style>

<!-- Backdrop nativo Zabbix -->
<div id="plt-modal-bg" class="overlay-bg" style="display:none;" onclick="pltCloseModal()"></div>

<!-- Modal nativo Zabbix (compartilhado entre técnico e reserva) -->
<div id="plt-modal" class="overlay-dialogue modal modal-popup modal-popup-medium"
     style="display:none; top:80px; left:50%; transform:translateX(-50%);" role="dialog" aria-modal="true">
    <div class="overlay-dialogue-header">
        <h4 id="plt-modal-title">Selecionar Técnico de Plantão</h4>
        <button class="btn-overlay-close" title="Close" onclick="pltCloseModal()"></button>
    </div>
    <div class="overlay-dialogue-body">
        <table class="list-table" id="plt-modal-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Telefone</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <?php
                $full_name   = htmlspecialchars($u['name'] . ' ' . $u['surname']);
                $display_val = $u['name'] . ' ' . $u['surname'] . ($u['phone'] ? ' (' . $u['phone'] . ')' : '');
            ?>
            <tr>
                <td>
                    <a href="javascript:void(0)"
                       onclick="pltPickUser(<?= (int)$u['userid'] ?>, <?= htmlspecialchars(json_encode($display_val)) ?>)">
                        <?= $full_name ?>
                    </a>
                </td>
                <td>
                    <?php if ($u['phone']): ?>
                        <?= htmlspecialchars($u['phone']) ?>
                    <?php else: ?>
                        <span style="color:#666;font-style:italic">sem telefone</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="overlay-dialogue-footer">
        <button type="button" class="btn-alt" onclick="pltCloseModal()">Cancelar</button>
    </div>
</div>

<div class="plt-wrap">

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

<div class="plt-nav">
    <button class="btn-icon zi-chevron-left" title="Mes anterior"
            onclick="location.href='<?= $prev_url ?>'"></button>
    <span class="plt-month-label"><?= $month_names[$month] . ' ' . $year ?></span>
    <button class="btn-icon zi-chevron-right" title="Proximo mes"
            onclick="location.href='<?= $next_url ?>'"></button>
    <button class="btn btn-alt" onclick="location.href='zabbix.php?action=phones.list'">Gerenciar Telefones</button>
</div>

<table class="plt-cal">
    <thead>
        <tr>
            <th>Segunda</th><th>Terca</th><th>Quarta</th>
            <th>Quinta</th><th>Sexta</th><th>Sabado</th><th>Domingo</th>
        </tr>
    </thead>
    <tbody>
<?php
$day          = 1;
$cell         = 0;
$delete_shown = [];

echo '<tr>';

for ($i = 0; $i < $first_weekday; $i++) {
    echo '<td class="empty"></td>';
    $cell++;
}

while ($day <= $days_in_month) {
    $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $week_key = getWeekMonday($year, $month, $day);
    $is_today = ($date_str === $today_str);
    $is_past  = ($date_str < $today_str);
    $entry    = $week_map[$week_key] ?? null;

    $cls = [];
    if ($is_today) $cls[] = 'today';
    if ($is_past)  $cls[] = 'past';
    if ($entry)    $cls[] = 'has-tech';
    $cls_str = $cls ? ' class="' . implode(' ', $cls) . '"' : '';

    $uid_js          = $entry ? (int) $entry['userid'] : 'null';
    $name_js         = $entry ? json_encode($entry['name'] . ' ' . $entry['surname'] . ($entry['phone'] ? ' (' . $entry['phone'] . ')' : '')) : 'null';
    $uid_res_js      = ($entry && $entry['userid_reserva']) ? (int) $entry['userid_reserva'] : 'null';
    $name_res_js     = ($entry && $entry['userid_reserva']) ? json_encode($entry['reserva_name'] . ' ' . $entry['reserva_surname'] . ($entry['reserva_phone'] ? ' (' . $entry['reserva_phone'] . ')' : '')) : 'null';
    echo '<td' . $cls_str . ' onclick="pltSelectDay(\'' . $date_str . '\',' . $uid_js . ',' . $name_js . ',' . $uid_res_js . ',' . $name_res_js . ')">';
    echo '<div class="plt-cell-inner">';

    echo '<div class="plt-day-num">' . $day;
    if ($is_today) echo '<span class="plt-today-badge">Hoje</span>';
    echo '</div>';

    if ($entry) {
        $name = htmlspecialchars($entry['name'] . ' ' . $entry['surname']);
        echo '<div class="plt-tech">' . $name . '</div>';
        if ($entry['phone']) {
            echo '<div class="plt-phone">' . htmlspecialchars($entry['phone']) . '</div>';
        } else {
            echo '<div class="plt-no-phone">sem telefone</div>';
        }
        if ($entry['userid_reserva']) {
            $rname = htmlspecialchars($entry['reserva_name'] . ' ' . $entry['reserva_surname']);
            echo '<div class="plt-reserva-label">Reserva</div>';
            echo '<div class="plt-tech-reserva">' . $rname . '</div>';
            if ($entry['reserva_phone']) {
                echo '<div class="plt-phone-reserva">' . htmlspecialchars($entry['reserva_phone']) . '</div>';
            }
        }
        if (!isset($delete_shown[$week_key])) {
            $del_url = 'zabbix.php?action=plantao.delete&scheduleid=' . (int)$entry['scheduleid']
                     . '&month=' . $month . '&year=' . $year;
            echo '<a class="plt-del" href="' . $del_url . '"'
               . ' tabindex="-1"'
               . ' onclick="event.stopPropagation();this.blur();return confirm(\'Remover plantao de ' . addslashes($name) . '?\')"'
               . '>remover</a>';
            $delete_shown[$week_key] = true;
        }
    }

    echo '</div></td>';
    $cell++;
    $day++;

    if ($cell % 7 === 0 && $day <= $days_in_month) {
        echo '</tr><tr>';
    }
}

$remaining = $cell % 7;
if ($remaining > 0) {
    for ($i = 0; $i < (7 - $remaining); $i++) {
        echo '<td class="empty"></td>';
    }
}
echo '</tr>';
?>
    </tbody>
</table>

<div class="plt-form-panel">
    <h3>Escalar Tecnico</h3>
    <form method="post" action="zabbix.php?action=plantao.save" id="plt-form">
        <input type="hidden" name="month" value="<?= $month ?>">
        <input type="hidden" name="year" value="<?= $year ?>">
        <input type="hidden" id="userid" name="userid" value="">
        <input type="hidden" id="userid_reserva" name="userid_reserva" value="">
        <input type="hidden" id="schedule_date" name="schedule_date" value="<?= $today_str ?>">
        <div class="plt-form-row">
            <label>Técnico:</label>
            <div class="plt-select-row">
                <div class="multiselect plt-multiselect-wrap">
                    <input type="text" id="plt-selected-name"
                           placeholder="Nenhum técnico selecionado"
                           autocomplete="off" oninput="pltAcFilter(this.value)">
                    <div class="plt-ac-dropdown" id="plt-ac-dropdown"></div>
                </div>
                <button type="button" class="btn" onclick="pltOpenModal('main')">Selecionar</button>
            </div>
            <label style="margin-left:16px;">Técnico reserva:</label>
            <div class="plt-select-row">
                <div class="multiselect plt-multiselect-wrap">
                    <input type="text" id="plt-selected-reserva"
                           placeholder="Nenhum técnico selecionado"
                           autocomplete="off" oninput="pltAcFilterReserva(this.value)">
                    <div class="plt-ac-dropdown" id="plt-ac-dropdown-reserva"></div>
                </div>
                <button type="button" class="btn" onclick="pltOpenModal('reserva')">Selecionar</button>
            </div>
            <button type="submit" class="btn" style="margin-left:8px;">Salvar Plantão da Semana</button>
        </div>
        <div class="plt-week-info" id="plt-week-info"></div>
    </form>
</div>

</div>

<script>
function pltWeekRange(dateStr) {
    var d = new Date(dateStr + 'T00:00:00');
    var dow = d.getDay();
    var diff = (dow === 0) ? 6 : dow - 1;
    var mon = new Date(d); mon.setDate(d.getDate() - diff);
    var sun = new Date(mon); sun.setDate(mon.getDate() + 6);
    function fmt(dt) {
        return ('0'+dt.getDate()).slice(-2)+'/'+('0'+(dt.getMonth()+1)).slice(-2)+'/'+dt.getFullYear();
    }
    return 'Semana: ' + fmt(mon) + ' (Seg) a ' + fmt(sun) + ' (Dom)';
}

function pltUpdateWeekInfo() {
    var val = document.getElementById('schedule_date').value;
    if (val) document.getElementById('plt-week-info').textContent = pltWeekRange(val);
}

// contexto do modal: 'main' ou 'reserva'
var pltModalCtx = 'main';

function pltSelectDay(date, userid, displayName, useridReserva, displayReserva) {
    document.getElementById('schedule_date').value = date;
    pltUpdateWeekInfo();
    if (userid !== null) {
        document.getElementById('userid').value = userid;
        document.getElementById('plt-selected-name').value = displayName || 'Técnico #' + userid;
    }
    if (useridReserva !== null) {
        document.getElementById('userid_reserva').value = useridReserva;
        document.getElementById('plt-selected-reserva').value = displayReserva || 'Técnico #' + useridReserva;
    } else {
        document.getElementById('userid_reserva').value = '';
        document.getElementById('plt-selected-reserva').value = '';
    }
    document.getElementById('plt-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function pltOpenModal(ctx) {
    pltModalCtx = ctx || 'main';
    document.getElementById('plt-modal-title').textContent =
        pltModalCtx === 'reserva' ? 'Selecionar Técnico Reserva' : 'Selecionar Técnico de Plantão';
    document.getElementById('plt-modal').style.display = '';
    document.getElementById('plt-modal-bg').style.display = '';
}

function pltCloseModal() {
    document.getElementById('plt-modal').style.display = 'none';
    document.getElementById('plt-modal-bg').style.display = 'none';
}

function pltPickUser(userid, displayName) {
    if (pltModalCtx === 'reserva') {
        document.getElementById('userid_reserva').value = userid;
        document.getElementById('plt-selected-reserva').value = displayName;
    } else {
        document.getElementById('userid').value = userid;
        document.getElementById('plt-selected-name').value = displayName;
    }
    pltCloseModal();
}

// lista de técnicos para autocomplete
var PLT_USERS = <?= json_encode(array_values(array_map(fn($u) => [
    'userid'  => (int)$u['userid'],
    'name'    => $u['name'] . ' ' . $u['surname'],
    'display' => $u['name'] . ' ' . $u['surname'] . ($u['phone'] ? ' (' . $u['phone'] . ')' : ''),
], $users))) ?>;

function pltAcHighlight(text, query) {
    if (!query) return text;
    var re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    return text.replace(re, '<mark>$1</mark>');
}

function pltAcBuild(ddId, hiddenId, inputId, q) {
    var dd = document.getElementById(ddId);
    if (!q) { dd.innerHTML = ''; dd.classList.remove('open'); return; }
    var ql = q.toLowerCase();
    var matches = PLT_USERS.filter(function(u) {
        return u.name.toLowerCase().indexOf(ql) !== -1;
    });
    if (!matches.length) { dd.innerHTML = ''; dd.classList.remove('open'); return; }
    dd.innerHTML = matches.map(function(u) {
        return '<div class="plt-ac-item" data-userid="' + u.userid + '" data-display="' + u.display.replace(/"/g,'&quot;') + '">'
            + pltAcHighlight(u.name, q) + '</div>';
    }).join('');
    dd.classList.add('open');
    dd.querySelectorAll('.plt-ac-item').forEach(function(el) {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            document.getElementById(hiddenId).value = this.dataset.userid;
            document.getElementById(inputId).value = this.dataset.display;
            dd.innerHTML = ''; dd.classList.remove('open');
        });
    });
}

function pltAcFilter(q) {
    pltAcBuild('plt-ac-dropdown', 'userid', 'plt-selected-name', q);
}

function pltAcFilterReserva(q) {
    pltAcBuild('plt-ac-dropdown-reserva', 'userid_reserva', 'plt-selected-reserva', q);
}

document.getElementById('plt-selected-name').addEventListener('blur', function() {
    setTimeout(function() {
        var dd = document.getElementById('plt-ac-dropdown');
        dd.innerHTML = ''; dd.classList.remove('open');
    }, 150);
});

document.getElementById('plt-selected-reserva').addEventListener('blur', function() {
    setTimeout(function() {
        var dd = document.getElementById('plt-ac-dropdown-reserva');
        dd.innerHTML = ''; dd.classList.remove('open');
    }, 150);
});

document.getElementById('schedule_date').addEventListener('change', pltUpdateWeekInfo);
// ESC também fecha
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') pltCloseModal();
});
pltUpdateWeekInfo();
</script>
<?php
$page_content = ob_get_clean();

$raw = new class($page_content) {
    public function __construct(private string $html) {}
    public function toString(bool $destroy = true): string { return $this->html; }
};

(new CHtmlPage())
    ->setTitle('Escala de Plantão')
    ->addItem($raw)
    ->show();
