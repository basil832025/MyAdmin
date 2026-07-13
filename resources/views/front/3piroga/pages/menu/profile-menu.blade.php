@php
    $accountMenu = \App\Support\Menus::bySlug('profile-menu');
@endphp
<x-ui.menu-list  :items="$accountMenu" :is_account_menu="true"  />
