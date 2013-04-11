var labelNames = new Array("label-success", "label-warning", "label-important", "label-info", "label-inverse");
var globalFeeds = new Object();
var globalRootFeed;

var curFeed = false;
function resize(){{{
	$("#feeds").height(window.innerHeight-($("#feeds").position().top + 40));
	$("#content").height($("#feeds").height() - ($("#content").position().top - $("#feeds").position().top) + 20);
}}}
window.onresize=resize;

function hideTags() {{{
	$(".navTag").remove();
	var specialTags = $("#specialTags");
	specialTags.find("i.icon-folder-open").toggleClass("icon-folder-close icon-folder-open");
	specialTags.unbind("click");
	specialTags.on("click", getTags);
}}}
function getTags(){{{
	var that = this;
	$("#spinFeed_specialTags").spin("tiny");
	$.ajax({
		url: "rest.php/tags",
		type: "GET",
		dataType: "json",
		complete: function(){ $("#spinFeed_specialTags").spin(false); },
		success: function(data){
			if (data.status != "OK"){
				alert(data.msg);
				return;
			}
			var specialTags = $("#specialTags");
			specialTags.find("i.icon-folder-close").toggleClass("icon-folder-close icon-folder-open");
			specialTags.unbind("click");
			specialTags.on("click", hideTags);
			$(".navTag").remove();
			data.tags = data.tags.reverse();
			for (var i = 0; i < data.tags.length; i++){
				var t = data.tags[i].tag;
				var tID = data.tags[i].ID;
				var a = $("<a href='#'>"+t+"</a>").attr("tagID", tID).attr("tag", t).on("click", function(){ showTag($(this).attr("tagID"), $(this).attr("tag")); });
				a.append("<span class='floatLeft spin' id='spinFeed_tag"+t+"'> </span>");
				specialTags.parent().after($("<li style='padding-left: 15px;' class='navTag'></li>").append(a));
			}
		}
	});
}}}
function search(){{{
	showEntries("rest.php/search/"+$("#frmSearch #txtSearch").val(), "spinFeed_specialSearch");
}}}

function showEntries(url, spin){{{
	var e = $("#entries");
	spin = "#"+spin;
	e.empty();
	e.append("<li class='nav-header'>Feedentries</li>");
	e.scroll(0);

	$(spin).spin("tiny");
	$.ajax({
		url: url,
		type: "GET",
		dataType: "json",
		complete: function(){ $(spin).spin(false); },
		success: function(data){
			var oldDate = "0000-00-00";
			$.each(globalFeeds, function(k,v){
				v.entries = new Object();
			});
			$.each(data, function(k,v){
				var newDate = v.date.match(/^(....)-0?(.?.)-0?(.?.)/);
				newDate = newDate[0];
				if (newDate != oldDate){
					oldDate = newDate;
					e.append("<li class='nav-header'>"+oldDate+"</li>");
				}
				new Entry(v);
			});
		}
	});
}}}
function showTag(tagID, tag){{{
	var e = $("#entries");
	e.empty();
	e.append("<li class='nav-header'>Feedentries</li>");
	e.scroll(0);
	$("#spinFeed_tag"+tag).spin("tiny");

	$.ajax({
		url: "rest.php/tags/"+tagID,
		type: "GET",
		dataType: "json",
		complete: function(){ $("#spinFeed_tag"+tag).spin(false); },
		success: function(data){
			$.each(data, function(k,v){
				$.each(globalFeeds, function(k,v){
					v.entries = new Object();
				});
				new Entry(v);
			});
		}
	});
}}}

function rearrangeFeeds(){{{
	var d = $("#rearrangeFeeds");
	var dBody = d.find(".modal-body").empty();
	dBody.tree(false);

	var tree = globalRootFeed.jqTree();

	var t = $("<div id='rearrangeTree' />").tree({
		data: [tree],
		autoOpen: 1,
		dragAndDrop: true,
		selectable: false,
		onCanMove: function(node) {
			/* Root feed can not be moved */
			if (!node.parent.parent) {
				return false;
			} else {
				return true;
			}
		},
		onCanMoveTo: function(moved_node, target_node, position) {
			/* Can only move inside directories
			 * Can not move outside Root feed
			 */
			if (position == "inside"){
				return target_node.feed.isDirectory;
			}
			if (!target_node.parent.parent){
				return false;
			}
			return true;
		}
	});

	t.bind(
		'tree.move',
		function(event){
			/* event.move_info.moved_node
			 * event.move_info.target_node
			 * event.move_info.position: ['before','after','inside']
			 * event.move_info.previous_parent
			 */
			var data = {};
			if (event.move_info.position == 'after'){
				data = { moveAfterFeed: event.move_info.target_node.feed.data.ID };
			}
			else if (event.move_info.position == 'before'){
				data = { moveBeforeFeed: event.move_info.target_node.feed.data.ID };
			}
			else if (event.move_info.position == 'inside'){
				data = { moveIntoCategory: event.move_info.target_node.feed.data.ID };
			}
			$.ajax({
				url: "rest.php/feed/"+event.move_info.moved_node.feed.data.ID+"/move",
				type: "POST",
				dataType: "json",
				data: data,
				success: function(data) {
					if (data.status != "OK"){
						alert(data.msg);
						return;
					}
					getFeeds();
				}
			});
		}
	);
	dBody.append(t);
	d.modal();

	return;
}}}
function FeedJqTree(){{{
	var that = this;

	var retVal = { id: this.data.ID, feed: this, label: this.data.name };
	if (this.children.length){
		retVal["children"] = [];
		for (var i = 0; i < this.children.length; i++){
			retVal.children[retVal.children.length] = this.children[i].jqTree();
		}
	}
	return retVal;
}}}
function FeedDirectories(){{{
	var that = this;

	if (!this.isDirectory){
		return [];
	}

	var retVal = [this];
	for (var ptr = 0; ptr < this.children.length; ptr++){
		if (this.children[ptr].isDirectory){
			retVal = retVal.concat(this.children[ptr].directories());
		}
	}
	return retVal;
}}}
function FeedCollapse(collapse){{{
	var that = this;
	this.folder.toggleClass("icon-folder-open icon-folder-close");
	for (var ptr = this.li.next(); ptr.length; ptr = ptr.next()){
		/* TODO: Is there a nicer way to do this? */
		if (parseInt(ptr.attr("startID")) >= that.data.startID && parseInt(ptr.attr("endID")) <= that.data.endID){
			if (collapse){
				ptr.hide();
			} else {
				ptr.show();
			}
		}
	}
	/* This might return false on when called from getFeeds()
	 * Collapsed state is stored serverside and we don't need to update it to its current value
	 */
	if ((this.data.collapsed == "yes") != collapse){
		$.ajax({
			url: "rest.php/feed/"+this.data.ID,
			type: "PUT",
			dataType: "json",
			data: JSON.stringify({
				collapsed: (collapse ? "yes" : "no")
			}),
			success: function(data){
				if (data.status != "OK"){
					alert(data.msg);
					return;
				}
				that.data.collapsed = (collapse ? "yes" : "no");
			}
		});
	}
}}}
function FeedShowSettings(){{{
	var that = this;

	var d = $("#feedSettings");
	d.find("#name").val(that.data.name);
	d.find("#url").val(that.data.url).attr("disabled", that.isDirectory);
	d.find("#isgroup").unbind("switch-change")
			  .on("switch-change", function(e, data){
				  var value = data.value;
				  d.find("#url").attr("disabled", value == "1");
			  })
	                  .switch("setState", that.isDirectory);
	d.find("#cacheimages").switch("setState", that.data.cacheimages == "yes");
	d.find("#deleteFeed").button().on("click", function(){
		if (confirm("Really delete feed? This cannot be undone!")){
			if (parseInt(that.data.startID) == 1){
				alert("Cowardly refusing to delete root category '"+that.data.name+"'");
			}
			else if (parseInt(that.data.endID) - 1 != parseInt(that.data.startID)){ // non-empty category
				alert("Cowardly refusing to delete non-empty category!");
			} else {
				d.dialog("close");
				that.deleteFeed();
			}
		}
	});

	var filter = d.find("#filter");
	filter.empty().spin("tiny");
	$.ajax({
		url: "rest.php/feed/"+that.data.ID+"/filter",
		type: "GET",
		dataType: "json",
		success: function (data){
			var s = "";
			data[data.length] = {ID: 0, regex: "", whiteorblack: "white"};
			$.each(data, function(k,v){
				s += "<tr><td>"+
					"<select class='selectpicker' name='whiteorblack_"+v.ID+"' id='whiteorblack_"+v.ID+"'>"+
					"<option value='white' "+(v.whiteorblack == "white" ? "selected" : "")+">Whitelist</option>"+
					"<option value='black' "+(v.whiteorblack == "black" ? "selected" : "")+">Blacklist</option>"+
					"<option value='ignore' "+(v.whiteorblack == "ignore" ? "selected" : "")+">Ignore entry</option>"+
					"</select>"+
					"</td><td>"+
					"<input type='text' value='"+v.regex+"' name='regex_"+v.ID+"'>"+
					"</td><td>"+
					"<label class='checkbox'><input type='checkbox' name='delete_"+v.ID+"' id='delete_"+v.ID+"'>Delete?</label>"+
					"</td></tr>";
			});
			filter.append(s).spin(false);
			filter.find(".selectpicker").selectpicker();
		},
		complete: function(){ d.find("input[type=radio]").button(); d.find("input[type=checkbox]").button(); }
	});

	$("#buttonDeleteFeed").unbind("click").bind("click", function(){ if (confirm("Really delete feed? This cannot be undone!")){ that.deleteFeed(); } });
	$("#buttonSaveChanges").unbind("click");
	$("#buttonSaveChanges").bind("click", function(){ that.updateFeed(); });
	$("#feedSettings").modal();
}}}
function FeedUpdateFeed(){{{
	var that = this;
	var d = $("#feedSettings");
	var filter = new Array();
	$.each(d.find("[name^=regex_]"), function(k,v){
		var id = v.name.split("_")[1];
		var regex = v.value;
		var wob = d.find("select[name=whiteorblack_"+id+"] :selected").val();
		var del = d.find("input[name=delete_"+id+"]").attr("checked") == "checked" ? "true" : "false";
		filter[filter.length] = {ID: id, regex: regex, whiteorblack: wob, delete: del};
	});
	$.ajax({
		url: "rest.php/feed/"+that.data.ID,
		type: "PUT",
		dataType: "json",
		data: JSON.stringify({
			name: d.find("#name").val(),
			url: (d.find("#isgroup input").attr("checked") == "checked" ? "" : d.find("#url").val()),
			cacheimages: d.find("#cacheimages input").attr("checked") == "checked" ? "yes" : "no",
			filter: filter
		}),
		success: function(data){
			if (data.status != "OK"){
				alert(data.msg);
				return;
			}
			that.data.name = d.find("#name").val();
			that.data.cacheimages = d.find("input[name=cacheimages]:checked").val();
			that.data.url = d.find("input[name=isgroup]:checked").val() == "1" ? "" : d.find("#url").val();
			that.nameFeed.empty().append(d.find("#name").val());
		},
		complete: function(){
			d.modal("hide");
		}
	});
}}}
function FeedRenderCount(){{{
	this.numNew.empty();
	if (parseInt(this.data.unreadCount) > 0){
		this.numNew.append(this.data.unreadCount);
		this.li.addClass("new");
	} else {
		this.li.removeClass("new");
	}
}}}
function FeedGetEntries(today){{{
	var that = this;

	if (curFeed){
		if (curFeed != this){
			/* Delete entries Object from memory.
			 * This is necessary for correct function of MarkAllRead
			 */
			delete(curFeed.entries);
		}
	}
	curFeed = this;

	that.spin.spin("tiny");
	$.ajax({
		url: "rest.php/feed/"+that.data.ID+"/entries/"+that.SQLDate(),
		type: "GET",
		dataType: "json",
		success: function(data){
			var e = $("#entries");

			$("ul#feeds li.active").removeClass("active");
			that.li.addClass("active");
			if (data.length == 0){
				e.find(".loadMore").remove();
				e.append("<li class='loadMore'>[ No more entries ]</li>");
				return;
			}

			e.append("<li class='nav-header'>"+data[0].date.substr(0, 10)+"</li>");
			var scrollTop = e.scrollTop();
			if (today){
				scrollTop = 0;
				$.each(globalFeeds, function(k,v){
					v.entries = new Object();
				});
			} else {
				e.find(".loadMore").remove();
			}

			var newDate;
			$.each(data, function(k, v){
				new Entry(v);
				newDate = v.date;
			});

			var li = $("<li class='loadMore' />");
			li.append(
				$("<a href='#'>[ Load next day ]</a>")
				.on("click", function(){
					var d = newDate.match(/^(....)-0?(.?.)-0?(.?.)/);
					that.date = new Date(parseInt(d[1]), parseInt(d[2])-1, parseInt(d[3])-1);
					that.getEntries(false);
				})
			);
			e.append(li);

			if (today){
				e.scrollTop(0);
			} else {
				e.scroll(scrollTop);
			}
		},
		complete: function(){ that.spin.spin(false); $("#entries").parent().spin(false); }
	});
}}}
function FeedMarkAllRead(){{{
	var that = this;
	var maxID;

	/* We're using the maximum entry ID we have to prevent the following race condition:
	 * Client gets entries
	 * Server updates feeds, gets new entries into database
	 * Client "marks all as read" which would also cause entries unknown to the client to be marked
	 */
	if (!(maxID = parseInt(that.data.maxID))){
		maxID = 0;
	}

	that.spin.spin("tiny");
	if (that.entries){
		$.each(that.entries, function(k,v){
			if (!v){
				return true;
			}
			maxID = Math.max(maxID, v.data.ID);
		});
	}

	$.ajax({
		url: "rest.php/feed/"+this.data.ID+"/markAllRead",
		dataType: "json",
		type: "POST",
		data: { maxID: maxID },
		complete: function(){ that.updateCount(); },
		success: function(data){
			if (data.status == "OK"){
				if (curFeed == that){
					/* TODO iterate through all new entries and turn this into real object oriented code */
					$("#entries").find("li.new").removeClass("new");
				}
			} else {
				alert(data.msg);
			}
		}
	});
}}}
function FeedUpdateCount(){{{
	var that = this;
	that.spin.spin("tiny");
	$.ajax({
		url: "rest.php/unreadcount/"+that.data.ID,
		dataType: "json",
		type: "GET",
		complete: function(){ that.spin.spin(false); },
		success: function(data){
			$.each(data, function(k, v){
				var feed = globalFeeds[parseInt(v.ID)];
				feed.data.unreadCount = v.unread;
				feed.data.maxID = v.maxID;
				feed.renderCount();
			})
			for (var x = that.parent; x; x=x.parent){
				/* Only need to update this feeds parents because we only get the unreadcount for this feeds children */
				var newCount = 0;
				for (var c = 0; c < x.children.length; c++){
					newCount += parseInt(x.children[c].data.unreadCount);
				}
				x.data.unreadCount = newCount;
				x.renderCount();
			}
		}
	});
}}}
function FeedDeleteFeed(){{{
	var that = this;
	that.spin.spin("tiny");

	$.ajax({
		url: "rest.php/feed/"+that.data.ID,
		type: "DELETE",
		dataType: "json",
		success: function(data){
			that.spin.spin(false);
			getFeeds();
		},
		complete: function(){
			$("#feedSettings").modal("hide");
		}
	});
}}}
function FeedSQLDate(){{{
	return this.date.getFullYear()+"-"+(this.date.getMonth()+1 < 10 ? "0" : "")+(this.date.getMonth()+1)+"-"+(this.date.getDate() < 10 ? "0" : "")+this.date.getDate();
}}}
function Feed(data){{{
	var that = this;
	globalFeeds[parseInt(data.ID)] = this;
	this.data = data;
	this.getEntries=FeedGetEntries;
	this.updateCount=FeedUpdateCount;
	this.markAllRead=FeedMarkAllRead;
	this.renderCount=FeedRenderCount;
	this.showSettings=FeedShowSettings;
	this.deleteFeed=FeedDeleteFeed;
	this.updateFeed=FeedUpdateFeed;
	this.collapse=FeedCollapse;
	this.SQLDate=FeedSQLDate;
	this.jqTree=FeedJqTree;
	this.directories=FeedDirectories;
	this.children = new Array();

	if (this.data.startID != "1"){
		/* unless this is the root feed, we need a pointer to our parent feed */
		/* TODO: Maybe there's an easier way than to parse the DOM tree, query its elements' attributes and then get an array element? */
		/* TODO: I'm quite sure there is */
		var parentFeed = $("li").filter(function(){
			return  $(this).attr("startID") <= parseInt(that.data.startID) &&
				$(this).attr("endID")   >= parseInt(that.data.endID)   &&
			globalFeeds[parseInt($(this).attr("ID"))].isDirectory;
		}).last().attr("ID");
		parentFeed = globalFeeds[parseInt(parentFeed)];
		this.parent = parentFeed;
		parentFeed.children[parentFeed.children.length] = this;
	}

	/* unfortunately, any of these tend to be sent from the server */
	if ("x"+this.data.url == "x" ||
		"x"+this.data.url == "xnull" ||
		"x"+this.data.url == "xundefined"){
		this.isDirectory=true;
	} else {
		this.isDirectory=false;
	}

	this.li = $("<li id='feed_"+this.data.startID+"' />");
	this.spin = $("<span class='floatLeft spin' id='spinFeed_"+this.data.startID+"'>&nbsp;</span>");
	this.numNew = $("<a id='numNew_"+this.data.startID+"' class='numNewMessages' />");
	this.nameFeed = $("<span href='#' class='nameFeed'>"+this.data.name+"</span>").append(this.numNew);

	this.buttons = new Object();
	/* This button will only be visible when the mouse overs over the feed/group to prevent cluttering the UI */
	this.buttons.settings = $("<i class='icon-pencil floatRight editButton' />")
		.on("click", function(){
			that.showSettings();
			return false; // last 'click' handler
		});
	this.buttons.newMessage = $("<i id='newMessage_"+this.data.startID+"' class='icon-envelope newmessage floatRight' />")
		.on("click", function() {
			that.markAllRead();
			return false; // last 'click' handler
		});

	var indent = 0;
	for (var ptr = this.parent; ptr; ptr=ptr.parent, indent += 16);

	this.li.attr("startID", this.data.startID)
		.attr("endID",   this.data.endID)
		.attr("ID",      this.data.ID)
		.css("padding-left", indent + "px")
		.append($("<a href='#' />")
			.append(this.buttons.settings)
			.append(this.buttons.newMessage)
			.append(this.spin)
			.append(this.nameFeed)
			.on("click", function(){
				/* Reset all feeds' entries Object.
				 * This is necessary for correct function of MarkAllRead
				 */
				$.each(globalFeeds, function(k,v){
					v.entries = new Object();
				});
				that.date = new Date();
				$("#entries").empty().append("<li class='nav-header'>Feedentries</li>");
				that.getEntries(true);
			})
		);

	$("#feeds").append(this.li);

	if (this.isDirectory){
		this.li.addClass("group");
		this.folder = $("<i class='icon-folder-open'></i>");
		this.folder.on("click", function(){
			var open = that.folder.hasClass("icon-folder-open");
			if (open){
				that.collapse(true);
			} else {
				that.collapse(false);
			}
			return false;
		});
		this.nameFeed.before(this.folder);
	} else {
		this.li.addClass("feed");
	}
}}}

function TagRemove(){{{
	var that = this;
	$.ajax({
		url: "rest.php/entry/"+that.entry.data.ID+"/tags/"+that.data.tag,
		type: "DELETE",
		dataType: "json",
		success: function(data){
			that.span.remove();
			var i = 0;
			$.each($("#headlineTags > .label"), function(k,v){
				v = $(v);
				for (var j=0; j<labelNames.length; j++){
					v.removeClass(labelNames[j]);
				}
				v.addClass(labelNames[i]);
			});
		}
	});
}}}
function Tag(entry, data){{{
	var that = this;
	this.remove = TagRemove;
	this.entry = entry;
	this.data = data;

	var a = $("<a title='Delete tag' href='#'>&times;</a>").on('click', function(){ that.remove() });
	var i = $("#headlineTags > .label").length;
	this.span = $("<span class='label "+(labelNames[i%labelNames.length])+"'>"+that.data.tag+" </span>")
			.append(a);

	$("#headlineTags").append(this.span);
}}}

function EntryMarkRead(){{{
	if (this.data.isread == "1"){
		return;
	}
	this.toggleRead();
}}}
function EntryToggleRead(){{{
	var that = this;
	if (this.data.isread == "1"){
		this.data.isread = "0";
	} else {
		this.data.isread = "1";
	}
	this.render();
	this.spin.spin("tiny");
	$.ajax({
		url: "rest.php/entry/"+that.data.ID,
		type: "PUT",
		dataType: "json",
		data: JSON.stringify({ isread: that.data.isread }),
		complete: function(){ that.spin.spin(false); },
		success: function(data){
			if (data.status == "OK"){
				var f;
				for (f = that.data.feed; f; f=f.parent){
					var i = parseInt(f.data.unreadCount);
					if (that.data.isread == "0"){
						i++;
					} else {
						i--;
					}
					f.data.unreadCount = i;
					f.renderCount();
				}
			} else {
				alert(data.msg);
			}
		}
	});
}}}
function EntryAddTag(){{{
	var that = this;
	var tag = $("#newTag").val();
	this.spin.spin("tiny");
	$.ajax({
		url: "rest.php/entry/"+that.data.ID+"/tags",
		type: "PUT",
		dataType: "json",
		data: JSON.stringify({ tags: tag }),
		complete: function(){ that.spin.spin(false); },
		success: function(data){
			if (data.status != "OK"){
				alert(data.msg);
				return;
			}
			that.tags[that.tags.length] = new Tag(that, {ID: 0, tag: tag});
		}
	});
}}}
function EntryShow(){{{
	var that = this;
	$("#content").empty().spin();
	$("#headline").empty().attr("href", "#").html("Loading...");
	that.spin.spin("tiny");
	$.ajax({
		url: "rest.php/entry/"+this.data.ID,
		type: "GET",
		dataType: "json",
		complete: function(){ that.spin.spin(false) },
		success: function(data){
			if (data.status == "error"){
				alert(data.msg);
				return;
			}
			$("#content").html(data.description.replace(/<(\/?)script/, "<$1disabledscript"));
			$("#headline").empty().attr("href", data.link).html("<nobr>"+data.title.replace(/</, "&lt;").replace(/>/, "&gt;")+"</nobr>");
			$("#headlineTags").empty();
			$("#btnAddNewTag").show();
			$("#frmAddNewTag").hide().unbind("submit").on("submit", function(){ that.addTag(); });
			that.tags = new Object();
			that.data.tags = data.tags;
			if (data.tags.length){
				for (var i = 0; i < data.tags.length; i++){
					that.tags[data.tags[i].ID] = new Tag(that, data.tags[i]);
				}
			}
			$("ul#entries li.active").removeClass("active");
			li = $("ul#entries li#entry_"+that.data.ID);
			li.addClass("active");
			that.markRead();
		}
	})
}}}
function EntryRender(){{{
	var that = this;
	if (this.data.isread == "1"){
		this.li.removeClass("new");
	} else {
		this.li.addClass("new");
	}
}}}
function EntryUpdate(){{{
	if (this.data.isread == "0"){
		this.li.addClass("new");
	} else {
		this.li.removeClass("new");
	}
	this.span.empty().append(this.data.title);
}}}
function Entry(data){{{
	var that = this;
	this.data = data;
	this.show = EntryShow;
	this.markRead = EntryMarkRead;
	this.toggleRead = EntryToggleRead;
	this.render = EntryRender;
	this.update = EntryUpdate;
	this.addTag = EntryAddTag;

	this.data.feed = globalFeeds[parseInt(this.data.feedID)];
	this.data.feed.entries[this.data.ID] = this;

	this.iconNew = $("<i class='newmessage floatLeft icon-asterisk' />")
		.on("click", function(){
			that.toggleRead();
			return false;
		});
	this.iconFav = $("<i class='floatLeft icon-star-empty' />")
		.on("click", function(){
			$.ajax({
				url: "rest.php/entry/"+that.data.ID,
				type: "PUT",
				dataType: "json",
				data: JSON.stringify({ favorite: (that.iconFav.hasClass("icon-star-empty") ? "yes" : "no") }),
				success: function(data){
					if (data.status == "OK"){
						that.iconFav.toggleClass("icon-star icon-star-empty");
					} else {
						alert(data.msg);
					}
				},
			});
			return false;
		});

	if (this.data.favorite == "yes"){
		this.iconFav.toggleClass("icon-star icon-star-empty");
	}

	this.spin = $("<span class='floatLeft spin' id='spinEntry_"+this.data.ID+"'>&nbsp;</span>");

	this.span = $("<span />");
	this.li = $("<li id='entry_"+this.data.ID+"' />")
		.append($("<a href='#' />")
			.append(this.spin)
			.append(this.iconFav)
			.append(this.iconNew)
			.append(this.span)
		)
		.on("click", function(){
			that.show();
		});

	this.update();
	$("#entries").append(this.li);
}}}

function getFeeds(){{{
	$("#feeds").find(".group").remove();
	$("#feeds").find(".feed").remove();

	$.ajax({
		url: "rest.php/feeds",
		type: "GET",
		dataType: "json",
		async: false,
		success: function(data){
			if (!data){
				return;
			}
			globalFeeds = new Object();
			$.each(data, function(k,v){
				if (k == 0){
					/* We can't assume that the root feed has the ID 1.
					 * Backwards compatibility means we might encounter
					 * an installation which didn't use a root directory.
					 */
				        globalRootFeed = new Feed(v);
					return true;
				}
				new Feed(v);
			});

			$.each(globalFeeds, function(k,v){
				if (v.data.collapsed == "yes"){
					v.collapse(true);
				}
			});
		},
	});
}}}
function startup(){{{
	/* Create bootstrap theme */
	$("input[type=button]").button();
	$(".selectpicker").selectpicker();

	$("#specialFavorites").on("click", function(){ showEntries("rest.php/favorites", "spinFeed_specialFavorites"); });
	$("#specialUnread").on("click", function(){ showEntries("rest.php/unread", "spinFeed_specialUnread"); });
	$("#specialTags").on("click", getTags);
	$("#btnAddNewTag").on("click", function(){ $("#btnAddNewTag").hide(); $("#frmAddNewTag").show(); });

	resize();
	getFeeds();
	getOptions();
}}}
$(document).ready(startup);

function addFeed(){{{
	var d = $("#addFeed");
	$.ajax({
		url: "rest.php/feeds",
		type: "POST",
		dataType: "json",
		data: {
			parent: d.find("#parent").val(),
			name: d.find("#name").val(),
			url: (d.find("#isgroup input").attr("checked") == "checked" ? "" : d.find("#url").val()),
			cacheimages: d.find("#cacheimages input").attr("checked") == "checked" ? "yes" : "no"
		},
		success: function(data){
			if (data.status == "OK"){
				getFeeds();
				d.modal("hide");
				return;
			}
			alert ("Error: "+data.msg);
			return;
		}
	});
}}}
function showAddFeed(){{{
	var d = $("#addFeed");

	var sortedFeeds = globalRootFeed.directories();

	var options = $("<select id='parent' class='selectpicker' />");
	$.each(sortedFeeds, function(k,v){
		var s = "";
		for (var x = v; x; x=x.parent){
			s = "/"+x.data.name+s;
		}
		options.append("<option value='"+v.data.ID+"'>"+s+"</option>");
	});
	options.val(globalRootFeed.data.ID);
	d.find("#controlParent").empty().append(options);
	options.selectpicker();
	d.find("#buttonAddRSSHandler").unbind("click");
	d.find("#buttonAddRSSHandler").bind("click", addRSSHandler);
	d.find("#buttonAddFeed").unbind("click").bind("click", addFeed);
	d.modal();
}}}
function addRSSHandler(){{{
	var addDir = $("#parent :selected");
	if (confirm("Add Feed handler to Firefox and place new feeds under "+addDir.text()+"?")){
		var l = window.location;
		var address = l.protocol+"/"+l.hostname+l.pathname.replace("index.html", "")+"add.php?url=%s&parentID="+addDir.val();
		window.navigator.registerContentHandler('application/vnd.mozilla.maybe.feed',address,"blindRSS " + addDir.text().replace(/^.*\//, ""));
	}
}}}
function showOptions(){{{
	var d = $("#modalOptions");
	d.modal();
}}}
function toggleTextOrIcons(textOrIcons){{{
	if (textOrIcons == "icons"){
		$(".titleText").hide();
		$(".titleIcon").show();
	} else {
		$(".titleText").show();
		$(".titleIcon").hide();
	}
}}}
function getOptions(){{{
	$.ajax({
		url: "rest.php/options",
		type: "GET",
		dataType: "json", 
		success: function(data){
			$("#selectPurgeAfter").val(data.purgeAfter.value);
			$("#buttonUnreadOnChange").attr("checked", data.unreadOnChange.value == "true" ? "checked" : "")
						  .switch("setState", data.unreadOnChange.value == "true");
			$("#buttonUnreadOnChange").on("switch-change", function(e, data){
				var value = data.value;
				setOption("unreadOnChange", value ? "true" : "false");
			});
			$("#selectPurgeAfter").on("change", function(e){
				var value = $("#selectPurgeAfter").val();
				setOption("purgeAfter", value);
			});
			$("#buttonDeleteFavorites").attr("checked", data.deleteFavorites.value == "yes" ? "checked" : "")
						  .switch("setState", data.deleteFavorites.value == "yes");
			$("#buttonDeleteFavorites").on("switch-change", function(e, data){
				var value = data.value;
				setOption("deleteFavorites", value ? "yes" : "no");
			});
			$("#buttonDeleteTagged").attr("checked", data.deleteTagged.value == "yes" ? "checked" : "")
						  .switch("setState", data.deleteTagged.value == "yes");
			$("#buttonDeleteTagged").on("switch-change", function(e, data){
				var value = data.value;
				setOption("deleteTagged", value ? "yes" : "no");
			});
			$("#buttonTextOrIcons").attr("checked", data.textOrIcons.value == "text" ? "checked" : "")
						  .switch("setState", data.textOrIcons.value == "text");
			toggleTextOrIcons(data.textOrIcons.value);
			$("#buttonTextOrIcons").on("switch-change", function(e, data){
				var value = data.value;
				setOption("textOrIcons", value ? "text" : "icons");
				toggleTextOrIcons(value ? "text" : "icons");
			});
		}
	});
}}}
function setOption(key, value){{{
	$.ajax({
		url: "rest.php/options/"+key,
		type: "POST",
		dataType: "json",
		data: { value: value },
		success: function(data) {
			if (data.status == "error"){
				alert(data.msg);
			}
		}
	});
}}}
