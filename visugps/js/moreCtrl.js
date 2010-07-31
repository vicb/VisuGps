/**
 * More Control extracted from ExtMapTypeControl <http://gmaps-utility-library-dev.googlecode.com/svn/trunk/extmaptypecontrol/>
 */

/**
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * @name ExtMapTypeControl
 * @version 1.5
 * @copyright 2007 Google
 * @author Pamela Fox, others
 * @copyright 2009 Wolfgang Pichler
 * @author Wolfgang Pichler (Pil), www.wolfpil.de
 * @fileoverview
 */

/**
 * @desc Constructor for MoreControl.
 * Immutable shared property moved to prototype.
 */
function MoreControl(layers) {
  MoreControl.prototype.layers = layers;
  this.chosen_ = [];
  this.boxes_ = [];
}

/**
* Inherits ExtMapTypeControl's prototypes only
*/
MoreControl.prototype = new GControl();

/*
 * Primarily creates an outer div that holds
 * all necessary elements needed for the more button.
 * @param {GMap2} map The map that has had this Control added to it.
 * @return {DOM Object} Div that holds all button elements.
 * @private
 */
MoreControl.prototype.initialize = function(map) {
  var me = this;
  me.map_ = map;
  var outer = document.createElement("div");
  me.moreDiv = me.createButton_("More...");
  me.moreDiv.setAttribute("title", "Show/Hide Layers");
  me.moreDiv.firstChild.style.width = "7em";
  me.toggleButton_(me.moreDiv.firstChild, false);
  outer.appendChild(me.moreDiv);
  outer.appendChild(me.createLayerBox_());

  GEvent.addDomListener(outer, "mouseover", function() {
  if(window.timer) clearTimeout(timer);
   me.layerboxDiv.style.display = "block";
   me.moreDiv.firstChild.style.height = "23px";
  });
  GEvent.addDomListener(outer, "mouseout", function() {
   timer = window.setTimeout(function() {
    me.layerboxDiv.style.display = "none";
    me.moreDiv.firstChild.style.height = "";
   }, 300);
  });
  GEvent.addDomListener(me.moreDiv, "click", function() {
   if(me.chosen_.length > 0 ) {
    /* Makes an independent copy of chosen array since it will be
    *  reset by switchLayer, which might not be useful here
    */
    var copy = me.chosen_.slice();
    for(var i = 0; i < copy.length; i++) {
     var index = parseInt(copy[i]);
     me.switchLayer_(true, me.layers[index].obj);
     me.boxes_[index].checked = true;
    }
   }
   else {
    me.hideAll_();
   }
  });
 map.getContainer().appendChild(outer);
 return outer;
};

/**
 * Primarily creates the outer div that holds the checkboxes.
 * @return {DOM Object} Div that holds all elements underneath the More...Button.
 * @private
 */
MoreControl.prototype.createLayerBox_ = function() {
  var me = this;
  me.layerboxDiv = document.createElement("div");
  // For nested elements position:absolute means relative to its parent
  me.layerboxDiv.style.position = "absolute";
  me.layerboxDiv.style.top = "20px";
  me.layerboxDiv.style.left = "0px";
  me.layerboxDiv.style.marginTop = "-1px";
  me.layerboxDiv.style.font = "small Arial";
  me.layerboxDiv.style.fontSize = "12px";
  me.layerboxDiv.style.padding = "4px";
  me.layerboxDiv.style.width = "120px";
  me.layerboxDiv.style.color = "#000";
  me.layerboxDiv.style.backgroundColor = "#fff";
  me.layerboxDiv.style.border = "1px solid gray";
  me.layerboxDiv.style.borderTopColor = "#e2e2e2";
  me.layerboxDiv.style.cursor = "default";

  var input = [];
  for (var i = 0; i < me.layers.length; i++) {
   input[i] = me.createCheckbox_(i, me.layers[i].name);
   me.layerboxDiv.appendChild(input[i] );
  }

  var ruler = document.createElement("hr");
  ruler.style.width = "92%";
  ruler.style.height = "1px";
  ruler.style.textAlign = "center";
  ruler.style.border = "1px";
  ruler.style.color = "#e2e2e2";
  ruler.style.backgroundColor = "#e2e2e2";
  var boxlink = document.createElement("a");
  boxlink.setAttribute("href", "javascript:void(0)");
  boxlink.style.color = "#a5a5a5";
  boxlink.style.textDecoration = "none";
  boxlink.style.cursor = "default";
  boxlink.style.marginLeft = "33px";
  boxlink.appendChild(document.createTextNode("Hide all"));

  me.layerboxDiv.appendChild(ruler);
  me.layerboxDiv.appendChild(boxlink);

  GEvent.addDomListener(boxlink, "click", function() {
   me.hideAll_();
  });
  me.layerboxDiv.style.display = "none";
  return me.layerboxDiv;
};

/**
 * Creates checkboxes with a click event inside of a div element.
 * @param {Number} nr The array index of the layers array
 * @param {String} name The name of the layer the checkbox belongs to
 * @return {DOM Object} Div that holds the checkbox and its related text node
 * @private
 */
MoreControl.prototype.createCheckbox_ = function(nr, name) {
  var me = this;
  var innerDiv = document.createElement("div");
  var checkbox = document.createElement("input");
  checkbox.setAttribute("type", "checkbox");
  var textSpan = document.createElement("span");
  textSpan.style.marginLeft = "2px";
  textSpan.appendChild(document.createTextNode(name));
  innerDiv.appendChild(checkbox);
  innerDiv.appendChild(textSpan);
  innerDiv.appendChild(document.createElement("br"));
  me.boxes_.push(checkbox);

  GEvent.addDomListener(checkbox, "click", function() {
   me.switchLayer_(this.checked, me.layers[nr].obj);
  });
  return innerDiv;
};

/**
 * Changes style of layerbox to appear on/off depending on passed boolean.
 * @param {DOM Object} elem element to change style of
 * @param {Boolean} boolCheck Used to decide between on or off style
 * @private
 */
MoreControl.prototype.toggleBox_ = function(elem, boolCheck) {
  elem.style.borderWidth = boolCheck ? "2px": "1px";
  elem.style.width = boolCheck ? "119px" : "120px";
};

/**
 * Adds and removes the chosen layers to/from the map.
 * Styles the link inside the layer box and the more button accordingly.
 * @param {Boolean} checked Value of checked or unchecked checkbox
 * @param {Object} layer The GLayer object to add or to remove
 * @private
 */
MoreControl.prototype.switchLayer_ = function(checked, layer) {
  var me = this;
  var link = me.layerboxDiv.lastChild;
  var button = me.moreDiv.firstChild;
  if(checked) {
   me.map_.addOverlay(layer);
   // Resets chosen array
   me.chosen_.length = 0;
   /*
   *  Toggles the elements
   */
   link.style.color = "#0000cd";
   link.style.textDecoration = "underline";
   link.style.cursor = "pointer";
   me.toggleButton_(button, true);
   me.toggleBox_(me.layerboxDiv, true);
  }
  else {
   me.map_.removeOverlay(layer);
   /*  Resets the elements
    * if all checkboxes were unchecked
   */
   if(!me.checkChecked()) {
    link.style.color = "#a5a5a5";
    link.style.textDecoration = "none";
    link.style.cursor = "default";
    me.toggleButton_(button, false);
    me.toggleBox_(me.layerboxDiv, false);
   }
  }
};

/**
 * Calls switchLayer to remove all displayed layers.
 * Stores index of removed layers in chosen array.
 * @private
 */
MoreControl.prototype.hideAll_ = function() {
  var me = this;
  for(var i = 0; i < me.boxes_.length; i++) {
   if(me.boxes_[i].checked) {
    me.boxes_[i].checked = false;
    me.switchLayer_(false, me.layers[i].obj);
    me.chosen_.push(i);
   }
  }
};

/**
 * Returns true if a checkbox is still checked, otherwise false.
 * @return {Boolean}
 */
MoreControl.prototype.checkChecked = function() {
  var me = this;
  for(var i = 0; i < me.boxes_.length; i++) {
   if(me.boxes_[i].checked) return true;
  }
  return false;
};

/**
 * Creates buttons with text nodes.
 * @param {String} text Text to display in button
 * @return {DOM Object} The div for the button.
 * @private
 */
MoreControl.prototype.createButton_ = function(text) {
  var buttonDiv = document.createElement("div");
  this.setButtonStyle_(buttonDiv);
  buttonDiv.style.cssFloat = "left";
  buttonDiv.style.styleFloat = "left";
  var textDiv = document.createElement("div");
  textDiv.appendChild(document.createTextNode(text));
  textDiv.style.width = "6em";
  buttonDiv.appendChild(textDiv);
  return buttonDiv;
};

/**
 * Sets the proper CSS for the given button element.
 * @param {DOM Object} button Button div to set style for
 * @private
 */
MoreControl.prototype.setButtonStyle_ = function(button) {
  button.style.color = "#000000";
  button.style.backgroundColor = "white";
  button.style.font = "small Arial";
  button.style.border = "1px solid black";
  button.style.padding = "0px";
  button.style.margin= "0px";
  button.style.textAlign = "center";
  button.style.fontSize = "12px";
  button.style.cursor = "pointer";
};

/**
 * Changes style of button to appear on/off depending on boolean passed in.
 * @param {DOM Object} div inner button div to change style of
 * @param {Boolean} boolCheck Used to decide to use on style or off style
 * @private
 */
MoreControl.prototype.toggleButton_ = function(div, boolCheck) {
  div.style.fontWeight = boolCheck ? "bold" : "normal";
  div.style.border = boolCheck ? "1px solid #483d8b" : "1px solid #fff";
  var shadow = boolCheck ? "#6495ed" : "#c0c0c0";
  var edges = ["RightColor", "BottomColor"];
   for (var j = 0; j < edges.length; j++) {
    div.style["border" + edges[j]] = shadow;
   }
};

/**
 * Required by GMaps API for controls.
 * @return {GControlPosition} Default location for map types buttons
 * @private
 */
MoreControl.prototype.getDefaultPosition = function() {
  return new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(98, 7));
};
