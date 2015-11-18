$(document).ready(function(){

  //Preloader
  $(window).load(function() {
    $("#loader").fadeOut();
    $("#mask").delay(1000).fadeOut("slow");
  });

  //Adding fixed position to header
  $(document).scroll(function() {
    if ($(document).scrollTop() >= 500) {
      $('.navbar').addClass('navbar-fixed-top');
      $('html').addClass('has-fixed-nav');
    } else {
      $('.navbar').removeClass('navbar-fixed-top');
      $('html').removeClass('has-fixed-nav');
    }
  });

  //Navigation Scrolling
  $('a[href*=#]').click(function() {
    if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
      if (target.length) {
        $('html,body').animate({
          scrollTop: target.offset().top
        }, 1000);
        return false;
      }
    }
  });

  //Close navbar on click
  $('.nav a').on('click', function(){
    if ($(window).width() < 768) {
      $(".navbar-toggle").click();
    }
  });

  //Nav Selection
  $('#nav').onePageNav({
    currentClass: 'active',
    scrollOffset: 50
  });

  //Home Text Slider
  $('.home-slider').flexslider({
      animation: "slide",
      directionNav: false,
      controlNav: false,
      direction: "vertical",
      slideshowSpeed: 5000,
      animationSpeed: 1000,
      smoothHeight: true,
      useCSS: false
  });

  //Get in touch slider
  $('.flexslider').flexslider({
      //animation: "slide",
      directionNav: false,
      controlNav: false,
      direction: "vertical",
      slideshowSpeed: 7000,
      animationSpeed: 1000,
      smoothHeight: true,
      useCSS: false
});

  //Elements animation
  $('.animated').appear(function(){
    var element = $(this);
    var animation = element.data('animation');
    var animationDelay = element.data('delay');
    if (animationDelay) {
      setTimeout(function(){
        element.addClass( animation + " visible" );
        element.removeClass('hiding');
        if (element.hasClass('counter')) {
          element.children('.value').countTo();
        }
      }, animationDelay);
    }else {
      element.addClass( animation + " visible" );
      element.removeClass('hiding');
      if (element.hasClass('counter')) {
        element.children('.value').countTo();
      }
    }    
  },{accY: -150});

  //Portfolio filters
  $('#projects-grid').mixitup({
    effects: ['fade','scale'],
    easing: 'snap'
  });

  //Portfolio project slider
  function initProjectSlider() {
    $('.project-slider').flexslider({
      prevText: "",
      nextText: "",
      useCSS: false,
      animation: "slide"
    });
  };

  //Portfolio Project Loading
  $('.open-project').click(function(){    
    var projectUrl = $(this).attr("href");      
    
    $('#project-content').animate({opacity:0}, 400,function(){
      $("#project-content").load(projectUrl);
      $('#project-content').delay(400).animate({opacity:1}, 400);
    });  
    
    //Project Page Open
    $('#project-extended').slideUp(600, function(){
      $('#project-extended').addClass('open');
      $('html, body').animate({ scrollTop: $(".portfolio-bottom").offset().top }, 900);
    }).delay(500).slideDown(600,function(){          
        $('#project-content').fadeIn('slow',function(){
          if ($('.project-slider').length > 0) {
            initProjectSlider();
          }
        });
    });

    return false;       
  
  });

  //Project Page Close
  $('#close-project').click(function(event) {
    $('#project-content').animate({opacity:0}, 400,function(){
      $('#project-extended').delay(400).slideUp(400).removeClass('open');
      $('html, body').animate({ scrollTop: $(".portfolio-top").offset().top -60}, 900);
    });
    return false;
  });

  //Pricing table selection
  $('.plan').click(function(){
    $('.plan').removeClass('selected');
    $(this).addClass('selected');
  });

  //Testimonials slider
  $('.testimonials-slider').flexslider({
    animation: "slide",
    directionNav: false,
    slideshowSpeed: 4000,
    useCSS: false
  });

  //Clients carousel
  $("#clients-carousel").owlCarousel({
    items : 4,
    itemsDesktop : [1000,4],
    itemsDesktopSmall : [900,3],
    itemsTablet: [600,2],
    itemsMobile : false,
    autoPlay: 4000,
    pagination: false
  });

  //tech carousel
  $("#tech-carousel").owlCarousel({
    items : 6,
    itemsDesktop : [1000,4],
    itemsDesktopSmall : [900,3],
    itemsTablet: [600,2],
    itemsMobile : false,
    autoPlay: 4000,
    pagination: false
  });

  
  //Google Maps
  function initMap() {
    var myLatlng = new google.maps.LatLng(40.773328,-73.960088); // <- Your latitude and longitude
    var styles = [
      {
        "featureType": "water",
        "stylers": [
        {
          "color": "#eee"
        },
        {
          "visibility": "on"
        }
        ]
      },
      {
        "featureType": "landscape",
        "stylers": [
        {
          "color": "#f2f2f2"
        }
        ]
      },
      {
        "featureType": "road",
        "stylers": [
        {
          "saturation": -100
        },
        {
          "lightness": 45
        }
        ]
      },
      {
        "featureType": "road.highway",
        "stylers": [
        {
          "visibility": "simplified"
        }
        ]
      },
      {
        "featureType": "road.arterial",
        "elementType": "labels.icon",
        "stylers": [
        {
          "visibility": "off"
        }
        ]
      },
      {
        "featureType": "administrative",
        "elementType": "labels.text.fill",
        "stylers": [
        {
          "color": "#444444"
        }
        ]
      },
      {
        "featureType": "transit",
        "stylers": [
        {
          "visibility": "off"
        }
        ]
      },
      {
        "featureType": "poi",
        "stylers": [
        {
          "visibility": "off"
        }
        ]
      }
     ]
    
    var mapOptions = {
      zoom: 16,
      center: myLatlng,
      mapTypeControl: false,
      disableDefaultUI: true,
      zoomControl: false,
      scrollwheel: false,
      styles: styles
    }
    var map = new google.maps.Map(document.getElementById('map'), mapOptions);

    var marker = new google.maps.Marker({
        position: myLatlng,
        map: map,
        title: 'We are here!'
    });
  }

  //google.maps.event.addDomListener(window, 'load', initMap);

  //Contact form validation and submit with ajax
  $('#contact-us').validate({
    errorPlacement: function(error, element) {},
    highlight: function(element, errorClass) {        
        $(element).parent().removeClass('success').addClass('error');
    },
    unhighlight: function(element, errorClass) {
        $(element).parent().removeClass('error').addClass('success');
    },
    rules: {
      fullname:{
        required: true
      },
      email: {
        required: true,
        email: true
      },
      phone: {
        required: true
      },
      message: {
        required: true
      }
    },
    submitHandler: function(form) {
      var url = $(form).attr('action');
      $.ajax({
        type: "POST",
        url: url,
        data: $(form).serialize(), // serializes the form's elements.
	statusCode: {
          500: function() {
            alert('Please complete the challenge and retry.');
         }
        },
        success: function(data)
        {
            $('.form-sent').slideDown(400); // show response from the php script.
        }
      });
    }
  });


  $(".morePosts").hide();

  $("#morePostsBtn").click(function(){
      var elem = document.getElementById("morePostsBtn");
      if (elem.value=="more posts"){
        elem.value = "less posts";
        $(".morePosts").show(300);
      } 
      else{
        elem.value = "more posts";
        $(".morePosts").hide(300);
      } 
  });


  var maxHeight=0;
  $(".ourNumbers").each(function(){
        if( $(".ourNumbers").outerHeight() > maxHeight){
          maxHeight = $(".ourNumbers").outerHeight();
        }
  });
  $(".ourNumbers").css("height", maxHeight+"px"); 
   
  /*************************************************************************************************************
  DESIGN-IT LAb  *********************************** General LAb *********************************** General LAb
  *************************************************************************************************************/ 

  /* monkeytables menus
  -------------------------------------------------- */
  $(".mt-btn").click(function(){
    var elem = (this.id);
    $(".mt-btn").removeClass('active');
    $("#" + elem).addClass('active');
    $(".mt-info").removeClass('show-mt-info');
    $("#mt-info-" + elem).addClass('show-mt-info');
  });

  /* Heights 
  -------------------------------------------------- */
  var hmax = $(window).height();
  var hfooter =$(".sticky-footer").height();

  /* Heights Blog */
  var hTitle =$("#dlabBlog .title").height();
  var hBorder = hmax-hfooter-hTitle-50;
  $("#dlabBlog .borderleft").css("min-height", hBorder); 

  var hentries = $("#dlabBlog .entries").height();
  var hoptionmenu = $("#dlabBlog .optionmenu").height();
  if (hentries > hoptionmenu) {
    $("#dlabBlog .borderleft").css("height", hentries); 
  };

  /* Heights Showcase  */
  var hTitle =$("#dlabShowcases .title").height();
  var hBorder = hmax-hfooter-hTitle-170;
  $("#dlabShowcases .borderright").css("min-height", hBorder); 

  /* Heights monkeyTables Docs, Support */ 
  var hBorder = hmax-hfooter-120;
  $("#monkeytablesDocs .borderleft").css("min-height", hBorder); 
  $("#monkeytablesSupport .borderleft").css("min-height", hBorder);

  var hentries = $("#monkeytables #demos .entries").height();
  var hoptionmenu = $("#monkeytables #demos .optionmenu").height();
  if (hentries < hoptionmenu) {
    $("#monkeytables #demos .borderleft").css("min-height", hoptionmenu);
  };

  
  $('.monkey-links').click(function() {
    var destination = $(this).attr('destination');
    //show new page
    $('.monkey-pages').hide();
    $('div.monkey-tables-'+destination).show();

    //change breadcrumb in navbar
    $('.monkey-links').removeClass('active');
    $('li.monkey-tables-'+destination).addClass('active');
  });
 
});


document.addEventListener("touchstart", function(){}, true);