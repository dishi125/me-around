var mainImagesFiles = [];

$(document.body).on('click','.removeImage > span',function(){
    var timestemp = $(this).attr('data-timestemp');
    if(timestemp){
        mainImagesFiles = $.grep(mainImagesFiles, function(e){ 
            return e.timestemp != timestemp; 
        });
        if(!mainImagesFiles.length){
            $("#main_image").val('');
        }
        console.log(mainImagesFiles)
    }
    $(this).parent().remove();
    $('.maxerror').remove();
});

function imagesPreview(input, placeToInsertImagePreview, isLimit) {
    var noImage = baseUrl + "/public/img/noImage.png";
    var isLimit = isLimit || 'yes';
    console.log(isLimit);
    console.log(noImage);
    mainImagesFiles = mainImagesFiles || [];
    console.log(input)
    if ((parseInt(input.files.length) + parseInt(mainImagesFiles.length)) > 4 && isLimit == 'yes'){
        var errorMsg = '<label class="error maxerror">You can only upload a maximum of 4 files.</label>';
        $(input).parent().parent().parent().find('label.error').remove();
        $(input).parent().parent().after(errorMsg);
        $("#main_image").val('');
    }else{
        if (input.files) {
            $('.maxerror').remove();
            Array.from(input.files).forEach(async (file,index) => { 
                var currentTimestemp = new Date().getTime()+''+index;
                file.timestemp = currentTimestemp;
                mainImagesFiles.push(file);
                var reader = await new FileReader(file);
                reader.onload = function(event) {
                    var bgImage = $($.parseHTML('<div>')).attr('style', 'background-image: url('+event.target.result+')').addClass("bgcoverimage").wrapInner("<img src='"+noImage+"' />"); 
                    var container = jQuery("<div></div>",{class: "removeImage mb-3", html:'<span data-timestemp="'+currentTimestemp+'" class="pointer"><i class="fa fa-times-circle fa-2x"></i></span>'});
                    container.append(bgImage);
                    container.appendTo(placeToInsertImagePreview);
                }
                reader.readAsDataURL(file);
            });
        }
    }
};