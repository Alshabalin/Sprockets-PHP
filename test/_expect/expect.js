jQuery();
// Generated by LiveScript 1.2.0
absolute();
var validators = {};
(function(){
  var Form, out$ = typeof exports != 'undefined' && exports || this;
  out$.Form = Form = (function(){
    Form.displayName = 'Form';
    var prototype = Form.prototype, constructor = Form;
    function Form(){}
    return Form;
  }());
  Form.Input = {};
}).call(this);


 // Generated by LiveScript 1.2.0
(function(){
  var Range;
  Form.Input.Range = Range = (function(){
    Range.displayName = 'Range';
    var prototype = Range.prototype, constructor = Range;
    prototype.type = 'Range';
    function Range(){}
    return Range;
  }());
}).call(this);
// Generated by CoffeeScript PHP 1.3.1
(function() {

  Form.Input.Text = (function() {

    Text.name = 'Text';

    function Text() {}

    Text.prototype.type = 'Text';

    return Text;

  })();

}).call(this);


  
var stateImg = document.getElementById('stateImg')

setInterval(function () {
  ajax('/api/server/state', function (data) {
    stateImg.src = data
  })
}, 500)

