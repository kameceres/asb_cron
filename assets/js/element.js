var Element = function(options) {
	// Type is html type of element
	this.type = '';
	
	// each attribute can be used for an attribute of HTML element
	this.attributes = {
		id: '',
		class: '',
		value: '',
		style: ''
	};
	
	this.text = '';
	
	// Children is an array of Elements
	this.children = [];
    
    // Member variable: Hold instance of this class
    var thisObj;
    
    // Function: Start the timer
    this.init = function() {
    	thisObj = this;
    };
    
    this.setText = function(text) {
    	thisObj.text = text;
    };
    
    this.setType = function(type) {
    	thisObj.type = type;
    };
    
    this.getType = function() {
    	return thisObj.type;
    };
    
    this.setAttributes = function(attrObj) {
    	thisObj.attributes = attrObj;
    };
    
    this.addAttribute = function(attribute, value) {
    	thisObj.attributes.attribute = value;
    };
    
    this.setChildren = function(children) {
    	thisObj.children = children;
    };
    
    this.addChild = function(child) {
    	thisObj.children[] = child;
    };
    
    this.toHtml = function() {
    	var html = thisObj.createTag(1);
    	
    	// ES6
    	for (const key of Object.keys(thisObj.attributes)) {
    		html += ' ' + key + '="' + obj[key] + '"';
    	}
    	
    	// ES6
    	html += thisObj.text;
    	for (let child of thisObj.children) {
    		html += child.toHtml();
		}
    	
    	html += thisObj.createTag(0);
    	
    	return html;
    };
    
    this.createTag = function(open) {
    	switch (thisObj.type) {
	        case 'div':
	        	return open == 1 ? '<div' : '</div>';
	            break;
	        case 'input':
	        	return open == 1 ? '<input' : '/>';
	            break;
	        default:
	        	return open == 1 ? '<div' : '</div>';
	    }
    };
    
    this.htmlByJson = function(json) {
    	thisObj.buildObjectByJson(json);
    	
    	return thisObj.toHtml();
    };
    
    this.buildObjectByJson(json) {
    	thisObj.setType(json.type);
    	thisObj.setAttributes(json.attributes);
    	thisObj.setText(json.text);
    	
    	for (let jsonChild of json.children) {
    		var newchild = new Element();
    		newchild.buildObjectByJson(jsonChild);
    		thisObj.addChild(newchild);
    	}
    };
    
    this.htmlByAjax = function(url, data, method) {
    	$.ajax({
			method: method,
			url: url,
			data: data,
			dataType: 'json',
			beforeSend: function() {
				// Display loading image
			},
			success: function(response) {
				if (response.status) {
					thisObj.buildObjectByJson(response.data);
				} else {
					$.notify(response.message, "warn");
				}
			}
		});
    };
};