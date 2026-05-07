<?php
function renderNav(): void {
    $role = $_SESSION['role'] ?? 'guest';
    $url  = APP_URL;
    $groups = [];
    if (in_array($role, ['buyer','admin','manager','superadmin'])) {
        $groups['buyer'] = [
            'label' => 'Покупатель',
            'items' => [
                ['href' => $url . '/buyer/index.php',   'label' => 'Мой кабинет'],
                ['href' => $url . '/buyer/orders.php',  'label' => 'Мои заказы'],
                ['href' => $url . '/buyer/cart.php',    'label' => 'Корзина'],
                ['href' => $url . '/buyer/profile.php', 'label' => 'Профиль'],
            ],
        ];
    }
    if (in_array($role, ['manager','superadmin'])) {
        $groups['manager'] = [
            'label' => 'Менеджер',
            'items' => [
                ['href' => $url . '/manager/index.php',      'label' => 'Обзор'],
                ['href' => $url . '/manager/parts.php',      'label' => 'Товары'],
                ['href' => $url . '/manager/categories.php', 'label' => 'Категории'],
                ['href' => $url . '/manager/brands.php',     'label' => 'Бренды'],
            ],
        ];
    }
    if (in_array($role, ['admin','superadmin'])) {
        $groups['admin'] = [
            'label' => 'Администратор',
            'items' => [
                ['href' => $url . '/admin/index.php',  'label' => 'Обзор'],
                ['href' => $url . '/admin/orders.php', 'label' => 'Заказы'],
                ['href' => $url . '/admin/users.php',  'label' => 'Пользователи'],
            ],
        ];
    }
    if ($role === 'superadmin') {
        $groups['superadmin'] = [
            'label' => 'Супер-Администратор',
            'items' => [
                ['href' => $url . '/superadmin/index.php',    'label' => 'Обзор'],
                ['href' => $url . '/superadmin/users.php',    'label' => 'Все пользователи'],
                ['href' => $url . '/superadmin/settings.php', 'label' => 'Настройки сайта'],
                ['href' => $url . '/superadmin/currencies.php','label'=> 'Курсы валют'],
                ['href' => $url . '/superadmin/warehouse.php', 'label'=> 'Склад Москвы'],
            ],
        ];
    }
    if (empty($groups)) return;
    $current = $_SERVER['REQUEST_URI'] ?? '';
    echo '<nav class="az-side-nav">';
    foreach ($groups as $key => $group) {
        echo '<div class="az-nav-group">';
        echo '<div class="az-nav-label">' . htmlspecialchars($group['label']) . '</div>';
        echo '<ul>';
        foreach ($group['items'] as $item) {
            $path   = parse_url($item['href'], PHP_URL_PATH);
            $active = (strpos($current, $path) !== false) ? ' active' : '';
            echo '<li><a href="' . $item['href'] . '" class="' . trim($active) . '">'
                . htmlspecialchars($item['label']) . '</a></li>';
        }
        echo '</ul></div>';
    }
    echo '</nav>';
}
?>
