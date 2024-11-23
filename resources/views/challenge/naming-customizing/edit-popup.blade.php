<?php
if (count($menus) > 0){
    foreach ($menus as $menu){
        if ($menu->id==1){
            $menu_eng_1 = $menu->eng_menu;
            $menu_kr_1 = $menu->kr_menu;
        }
        elseif ($menu->id==2){
            $menu_eng_2 = $menu->eng_menu;
            $menu_kr_2 = $menu->kr_menu;
        }
        elseif ($menu->id==3){
            $menu_eng_3 = $menu->eng_menu;
            $menu_kr_3 = $menu->kr_menu;
        }
    }
}
?>

<div class="modal-dialog" style="max-width: 50%;">
    <div class="modal-content">
        <form id="menuForm" method="post">
            {{ csrf_field() }}
            <div class="modal-header justify-content-center">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <div class="align-items-xl-center mb-3">
                    <div class="row mb-2">
                        <div class="col-md-2">
                        </div>
                        <div class="col-md-3">
                            <h3 class="text-center">1</h3>
                        </div>
                        <div class="col-md-3">
                            <h3 class="text-center">2</h3>
                        </div>
                        <div class="col-md-3">
                            <h3 class="text-center">3</h3>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-2">
                            <h5>English</h5>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="menu_eng_1" id="menu_eng_1" class="form-control" placeholder="Menu name 1" value="{{ isset($menu_eng_1) ? $menu_eng_1 : "" }}"/>
                            @error('menu_eng_1')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="menu_eng_2" id="menu_eng_2" class="form-control" placeholder="Menu name 2" value="{{ isset($menu_eng_2) ? $menu_eng_2 : "" }}"/>
                            @error('menu_eng_2')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="menu_eng_3" id="menu_eng_3" class="form-control" placeholder="Menu name 3" value="{{ isset($menu_eng_3) ? $menu_eng_3 : "" }}"/>
                            @error('menu_eng_3')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-2">
                            <h5>Korean</h5>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="menu_kr_1" id="menu_kr_1" class="form-control" placeholder="Menu name 1" value="{{ isset($menu_kr_1) ? $menu_kr_1 : "" }}"/>
                            @error('menu_kr_1')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="menu_kr_2" id="menu_kr_2" class="form-control" placeholder="Menu name 2" value="{{ isset($menu_kr_2) ? $menu_kr_2 : "" }}"/>
                            @error('menu_kr_2')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="menu_kr_3" id="menu_kr_3" class="form-control" placeholder="Menu name 3" value="{{ isset($menu_kr_3) ? $menu_kr_3 : "" }}"/>
                            @error('menu_kr_3')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{!! __(Lang::get('general.close')) !!}</button>
                <button type="submit" class="btn btn-primary" id="save_btn">Save</button>
            </div>
        </form>
    </div>
</div>
