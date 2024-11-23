<?php
$data = [];
if (!empty($menu)) {
    $data = $menu;
}
$id = !empty($data['id']) ? $data['id'] : '';
$menu_name = !empty($data['menu_name']) ? $data['menu_name'] : '';

?>
<div class="modal-dialog " style="max-width: 30%">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Menu Setting</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            {!! Form::open([
                'route' => ['admin.important-setting.menu.update', $id],
                'id' => 'menuSettingForm',
                'method' => 'put',
                'enctype' => 'multipart/form-data',
            ]) !!}
            @csrf
            <div class="card-body p-0">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('menu_name', 'Menu Name') !!}
                            {!! Form::text('menu_name', $menu_name, [
                                'class' => 'form-control',
                                'placeholder' => "Menu Name",
                            ]) !!}
                        </div>
                    </div>
                    @foreach($postLanguages as $postLanguage)
                        <div class="col-md-12">
                            <?php
                                $menuData = collect($menuLanguage)->where('language_id',$postLanguage->id)->first();
                                $mName = ($menuData && !empty($menuData->menu_name)) ? $menuData->menu_name : '';
                            ?>
                            <div class="form-group">
                                {!! Form::label('menu_language_name['.$postLanguage->id.']', 'Menu Name ('.$postLanguage->name.")") !!}
                                {!! Form::text('menu_language_name['.$postLanguage->id.']', $mName, [
                                    'class' => 'form-control',
                                    'placeholder' => "Menu Name (".$postLanguage->name.")",
                                ]) !!}
                            </div>
                        </div>
                    @endforeach
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                        <button type="button" class="btn btn-primary" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">{{ __(Lang::get('general.cancel')) }}</span>
                        </button>
                    </div>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
