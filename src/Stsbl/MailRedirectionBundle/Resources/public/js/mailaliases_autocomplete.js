/* 
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
IServ.MailAliases = {};

IServ.MailAliases.Autocomplete = IServ.register(function(IServ) {
    "use strict";

    var thOptions = {
        minLength: 0
    };
    var thSourceUser = {
    	remote: IServ.Routing.generate('admin_mail_aliases_recipients') + '?type=user&query=%QUERY',
        templates: {
            suggestion: renderSuggestion
        }
    };

    var thSourceGroup = {
        remote: IServ.Routing.generate('admin_mail_aliases_recipients') + '?type=group&query=%QUERY',
        templates: {
            suggestion: renderSuggestion
        }
    };

    function initialize()
    {
        var groupCollection = $('.mail-aliases-recipient-group-autocomplete');
        var userCollection = $('.mail-aliases-recipient-user-autocomplete');
        
    	IServ.Autocomplete.make(groupCollection, thSourceGroup, thOptions);
        IServ.Autocomplete.make(userCollection, thSourceUser, thOptions);
        consoleLog('IServ.MailAliases.Autocomplete.initalize: Autocompleter registered');
        
        groupCollection.initialize( function (e) {
            IServ.Autocomplete.make($('.mail-aliases-recipient-group-autocomplete'), thSourceGroup, thOptions);
            consoleLog('IServ.MailAliases.Autocomplete.initalize: Autocompleter registered');
        });
        userCollection.initialize( function (e) {
            IServ.Autocomplete.make($('.mail-aliases-recipient-user-autocomplete'), thSourceUser, thOptions);
            consoleLog('IServ.MailAliases.Autocomplete.initalize: Autocompleter registered');
        });
    }
    
    //see mail compse.js
    function renderSuggestion(data)
    {
        var icon;
        var ext = '';

        switch(data.source) {
            case 'group': 
                icon = 'pro-group'; 
                break;
            case 'user':
                icon = 'pro-user'; 
                break;
            default: 
                icon = 'envelope';
        }

        return '<p>' + IServ.Icon.get(icon) + ' ' + data.label + ext + '</p>';
    }
    
    // for console logging in debug mode
    function consoleLog(text)
    {
        if (IServ.App.isDebug()) {
            console.log(text);
        }
    }

    // Public API
    return {
        init: initialize
    };

}(IServ)); // end of IServ module
