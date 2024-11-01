(function($) {

$(document).ready(function(){
        console.log("initializing slick."); 
        var config = {
                enabled: true, 
                autoplay: false, 
                draggable: true,
                method: {}, 
                }

        $('.slick').on('init', function (event, slick)
                                        {
                                        console.log("running slick init function.");

                                        var template_id = $('#wcloyality_field_template_id').val();

                                        console.log(template_id); 

                                        var selected_template_frame  =  $("#template-select").find("div[data-template='" + template_id + "']"); 
                                        
                                        console.log("Frame ID:" + selected_template_frame.data('slick-index'));

                                        var index = selected_template_frame.data('slick-index');
 
                                        
                                        slick.slickGoTo(index);
                                        })
                   

        $('.slick').on('afterChange', function(event, slick, currentSlide, nextSlide) 
                        {
                        console.log("running slick after change:" + currentSlide)
                        }); 

        $('.slick').on('beforeChange',  function (event, slick, currentSlide, nextSlide) 
                        {
                        console.log("running slick before change:" + nextSlide); 

                        var template = $("div[id='template-index-" + nextSlide + "']" ).attr('data-template');
                        
                        console.log("template: " + template); 

                        $('#wcloyality_field_template_id').val(template);
                        });
                        
        $('.slick').slick(config); 
      });
      
})(jQuery);