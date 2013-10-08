/*
 * Copyright 2010 Guillaume Bort
 * http://github.com/guillaumebort/jquery-ndd
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

jQuery.fn.extend({

    droppable: function(enter, leave, drop) {
        
        var currents = {}, uuid = 0;
            
        this.live('dragenter dragleave dragover drop', function(e) {
            
            if(!this.uuid) {
                this.uuid = ++uuid;
                currents[this.uuid] = {hover: false, leaveTimeout: null};
            }
            
            if(e.type == 'dragenter' || e.type == 'dragover') {
                
                clearTimeout(currents[this.uuid].leaveTimeout);
                
                if(!currents[this.uuid].hover) {
                   	if(enter) enter.apply(this, [e]);
                    currents[this.uuid].hover = true;
                }
            } 
             
            if(e.type == 'dragleave') {
            
                if(currents[this.uuid].hover) {
                    var self = this;
                    currents[this.uuid].leaveTimeout = setTimeout(function() {
                        if(leave) leave.apply(self, [e]);
                        currents[self.uuid].hover = false;
                    }, 50);
                }
            }  
            
            if(e.type == 'drop') {
            
                if(currents[this.uuid].hover) {
                    if(leave) leave.apply(this, [e]);
                    currents[this.uuid].hover = false;
                    if(drop) drop.apply(this, [e]);
               }
            }
                
        });
   	}
});


