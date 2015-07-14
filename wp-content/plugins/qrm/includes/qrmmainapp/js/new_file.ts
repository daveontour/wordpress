/**
 * New typescript file
 */
 class Greeter {
	/* This comment may span multiple lines. */
	constructor(public greeting: string) { }
	// This comment may span only this line
	greet(dave:string) {
		return "<h1>" + this.greeting + dave +"</h1>";
	}
	hate() {
		return "Haters guna hate, hate, hate"
	}
};
var greeter = new Greeter("Hello, world!");
var str = greeter.greet();
document.body.innerHTML = str;
