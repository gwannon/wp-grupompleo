// quick search regex
var qsRegex;
var selectedRadios = [];

// init Isotope
var iso = new Isotope( '.jobs-grid', {
  itemSelector: '.jobs-item',
  layoutMode: 'fitRows',
  filter: function( itemJob ) {
    console.log("FILTER: "+itemJob.textContent);
    if(qsRegex) {
      console.log(1);
      if(itemJob.textContent.match( qsRegex )) {
        if(selectedRadios.length === 0) return true;
        else {
          var control = 0;  
          selectedRadios.forEach((element) => {
            if (itemJob.classList.contains(element)) control++;
          });
          if(control == selectedRadios.length) return true;
          else return false;
        }
      } else {
        return false;
      }
     } else {
      if(selectedRadios.length === 0) return true;
        else {
          var control = 0;  
          selectedRadios.forEach((element) => {
            if (itemJob.classList.contains(element)) control++;
          });
          if(control == selectedRadios.length) return true;
          else return false;
        }
      return true;
     } 
  },
});
iso.arrange();

// use value of search field to filter
var quicksearch = document.querySelector('.quicksearch');
quicksearch.addEventListener( 'keyup', debounce( function() {
  qsRegex = new RegExp( quicksearch.value, 'gi' );
  iso.arrange();
}, 200 ) );

// debounce so filtering doesn't happen every millisecond
function debounce( fn, threshold ) {
  var timeout;
  threshold = threshold || 100;
  return function debounced() {
    clearTimeout( timeout );
    var args = arguments;
    var _this = this;
    function delayed() {
      fn.apply( _this, args );
    }
    timeout = setTimeout( delayed, threshold );
  };
}

document.querySelectorAll("input[type='radio']").forEach((element) => {
  element.addEventListener('change',function(){
    selectedRadios = [];
    document.querySelectorAll("input[type='radio']:checked").forEach((element) => {
      if(element.value!= '') selectedRadios.push(element.value);
    });
    iso.arrange();
  });
});