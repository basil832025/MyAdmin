<?= '<'.'?'.'xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
        <title>{{ $feedTitle }}</title>
        <link>{{ $feedLink }}</link>
        <description>{{ $feedDescription }}</description>
@foreach($items as $item)
        <item>
            <g:id>{{ $item['id'] }}</g:id>
            <g:title><![CDATA[{{ $item['title'] }}]]></g:title>
            <g:description><![CDATA[{{ $item['description'] }}]]></g:description>
            <g:link>{{ $item['link'] }}</g:link>
            <g:image_link>{{ $item['image_link'] }}</g:image_link>
            <g:condition>{{ $item['condition'] }}</g:condition>
            <g:availability>{{ $item['availability'] }}</g:availability>
            <g:price>{{ $item['price'] }}</g:price>
            <g:new>{{ $item['new'] }}</g:new>
            <g:google_product_category><![CDATA[{{ $item['google_product_category'] }}]]></g:google_product_category>
            <g:product_type><![CDATA[{{ $item['product_type'] }}]]></g:product_type>
@if(!empty($item['item_group_id']))
            <g:item_group_id>{{ $item['item_group_id'] }}</g:item_group_id>
@endif
        </item>
@endforeach
    </channel>
</rss>
