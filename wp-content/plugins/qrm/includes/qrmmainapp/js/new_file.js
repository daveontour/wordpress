/**
 * New typescript file
 */
var Greeter = (function () {
    /* This comment may span multiple lines. */
    function Greeter(greeting) {
        this.greeting = greeting;
    }
    // This comment may span only this line
    Greeter.prototype.greet = function (dave) {
        return "<h1>" + this.greeting + dave + "</h1>";
    };
    Greeter.prototype.hate = function () {
        return "Haters guna hate, hate, hate";
    };
    return Greeter;
})();
;
var greeter = new Greeter("Hello, world!");
var str = greeter.greet();
document.body.innerHTML = str;
//# sourceMappingURL=new_file.js.map