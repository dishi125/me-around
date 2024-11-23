<?php 
    $fileMimeType = '';
    $BGExt = pathinfo($backgroundRiv, PATHINFO_EXTENSION);
    $FGExt = pathinfo($fileData, PATHINFO_EXTENSION);
    $videoExt = ['mp4','mov','wmv','avi','flv','mkv'];

    if(in_array($BGExt,$videoExt)){
        $fileMimeType = \GuzzleHttp\Psr7\mimetype_from_filename($backgroundRiv); 
    }
?>
<div class="modal-dialog viewCardModel" >
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>@if (@$title) {{ @$title }} @endif
            
                <a href="javascript:void(0);" class="sizebutton btn btn-primary ml-2" size-type="small">Small</a>
                <a href="javascript:void(0);" class="sizebutton btn btn-primary ml-2" size-type="medium">Medium</a>
                <a href="javascript:void(0);" class="sizebutton btn btn-primary ml-2" size-type="large">Large</a>
            </h5> 
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center position-relative  text-center">    
            @if($BGExt == 'riv')        
                <canvas id="background_canvas" height="600" width="600"></canvas>
            @elseif (in_array($BGExt,$videoExt))
                <video id="background_canvas" class="background_canvas_video" width="600" height="600" autoplay loop muted playinshit playsinline>
                    <source src="{{$backgroundRiv}}" type="{{$fileMimeType}}">
                    Your browser does not support the video tag.
                </video>
            @else
                <div id="background_canvas" style="background-image: url('{{$backgroundRiv}}')" ></div>
            @endif

            @if($FGExt == 'riv')  
                <canvas id="forground_canvas" width="600" height="600"></canvas>
            @else 
                <div id="forground_canvas" class="img" style="background-image: url('{{$fileData}}')" ></div>
            @endif
        </div>
    </div>
</div>

<script>
    $(function() {
        var bgHeight = $("#background_canvas").height();
        $(document).on("click",".sizebutton",function(){
            var size = $(this).attr("size-type");
            var updateHeightSize = bgHeight;

            if(size == 'small'){
                updateHeightSize = bgHeight - 200;
            }else if(size == 'medium'){
                updateHeightSize = bgHeight - 100;
            }

            $(".viewCardModel").css("min-width", updateHeightSize+50);
            $("#forground_canvas").css("height", updateHeightSize);
            $("#forground_canvas").css("width", updateHeightSize);
            $("#background_canvas").css("cssText", "height: "+updateHeightSize+"px !important; width: "+updateHeightSize+"px !important;");
            
            console.log(updateHeightSize);
            console.log(bgHeight);
            console.log(size);
        });

        if("{{$BGExt}}" == 'riv'){
            new rive.Rive({
                src: "{{$backgroundRiv}}",
                canvas: document.getElementById('background_canvas'),
                autoplay: true,
            });
        }
        if("{{$FGExt}}" == 'riv'){
            new rive.Rive({
                src: '{{$fileData}}',
                canvas: document.getElementById('forground_canvas'),
                autoplay: true,
            });
        }
    });
</script>

