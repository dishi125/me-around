<div class="buttons">
    <a href="{!! route('admin.important-setting.index') !!}" class="btn btn-primary mt-2">Credit Rating Custom</a>
    <a href="{!! route('admin.important-setting.limit-custom.index') !!}" class="btn btn-primary mt-2 {{$active == 'limit' ? 'active' : ''}}">Limit Custom</a>
    <a href="{!! route('admin.important-setting.limit-custom.index-links') !!}" class="btn btn-primary mt-2 {{$active == 'links' ? 'active' : ''}}">Links</a>
    <a href="{!! route('admin.explanation.index') !!}" class="btn btn-primary mt-2 {{$active == 'explanation' ? 'active' : ''}}">Explanation</a>
    <a href="{!! route('admin.important-setting.show-hide.index') !!}" class="btn btn-primary mt-2 {{$active == 'show_hide' ? 'active' : ''}}">Show & Hide</a>
    <a href="{!! route('admin.important-setting.menu-setting.index') !!}" class="btn btn-primary mt-2 {{$active == 'menu_setting' ? 'active' : ''}}">Menu Settings</a>
    <a href="{!! route('admin.important-setting.category-setting.index') !!}" class="btn btn-primary mt-2 {{$active == 'category_setting' ? 'active' : ''}}">Category Settings</a>
    <a href="{!! route('admin.important-setting.app-version.index') !!}" class="btn btn-primary mt-2 {{$active == 'app_version' ? 'active' : ''}}">Manage App Version</a>
    <a href="{!! route('admin.important-setting.policy-pages.index') !!}" class="btn btn-primary mt-2 {{$active == 'cms_pages' ? 'active' : ''}}">Policy Pages</a>
    <a href="{!! route('admin.important-setting.instagram-settings') !!}" class="btn btn-primary mt-2 {{$active == 'instagram' ? 'active' : ''}}">Instagram Settings</a>
    <a href="{!! route('admin.important-setting.payple-setting.index') !!}" class="btn btn-primary mt-2 {{$active == 'payple' ? 'active' : ''}}">Payple Settings</a>
</div>
