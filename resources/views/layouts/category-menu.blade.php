<div class="card">
    <div class="card-body">
        <div class="buttons">
            <a href="{!! route('admin.category.index') !!}" class="btn btn-primary">Hospital</a>
            <a href="{!! route('admin.category.shop.index') !!}" class="btn btn-primary">Shop</a>
            <a href="{!! route('admin.category.shop.index', ['custom' => \App\Models\CategoryTypes::SHOP2]) !!}" class="btn btn-primary">Shop 2</a>
            <a href="{!! route('admin.category.community.index') !!}" class="btn btn-primary">Community</a>
            <a href="{!! route('admin.category.suggest.index') !!}" class="btn btn-primary">Suggest</a>
            <a href="{!! route('admin.category.suggest.index', ['custom' => \App\Models\CategoryTypes::CUSTOM2]) !!}" class="btn btn-primary">Suggest 2</a>
            <a href="{!! route('admin.category.report.index') !!}" class="btn btn-primary">Report</a>
            <a href="{!! route('admin.currency.index') !!}" class="btn btn-primary">Currency</a>
        </div>
    </div>
</div>