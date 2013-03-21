var globalFeeds = new Array();
var globalRootFeed;
var globalUlEntries;

var curFeed = false;
function min(a, b){{{
	a = parseInt(a);
	b = parseInt(b);
	if (a < b){
		return a;
	}
	return b;
}}}
function max(a, b){{{
	a = parseInt(a);
	b = parseInt(b);
	if (a > b){
		return a;
	}
	return b;
}}}
function resize(){{{
	$("#feeds").height(window.innerHeight-($("#feeds").position().top + 40));
	$("#content").height($("#feeds").height() - ($("#content").position().top - $("#feeds").position().top) + 20);
}}}
window.onresize=resize;

function showFavorites(){{{
	var e = globalUlEntries;
	e.empty();
	e.append("<li class='nav-header'>Feedentries</li>");
	e.scroll(0);

	$.ajax({
		url: "rest.php/favorites",
		type: "GET",
		dataType: "json",
		success: function(data){
			$.each(data, function(k,v){
				for (var ptr=0; ptr<globalFeeds.length; ptr++){
					if (globalFeeds[ptr])
						globalFeeds[ptr].entries = new Object();
				}
				new Entry(v);
			});
		}
	});
}}}
function showUnread(){{{
	var e = globalUlEntries;
	e.empty();
	e.append("<li class='nav-header'>Feedentries</li>");
	e.scroll(0);

	$.ajax({
		url: "rest.php/unread",
		type: "GET",
		dataType: "json",
		success: function(data){
			$.each(data, function(k,v){
				for (var ptr=0; ptr<globalFeeds.length; ptr++){
					if (globalFeeds[ptr])
						globalFeeds[ptr].entries = new Object();
				}
				new Entry(v);
			});
		}
	});
}}}
function showTag(tag){{{
	var e = globalUlEntries;
	e.empty();
	e.append("<li class='nav-header'>Feedentries</li>");
	e.scroll(0);

	$.ajax({
		url: "rest.php/tags/"+tag,
		type: "GET",
		dataType: "json",
		success: function(data){
			$.each(data, function(k,v){
				for (var ptr=0; ptr<globalFeeds.length; ptr++){
					if (globalFeeds[ptr])
						globalFeeds[ptr].entries = new Object();
				}
				new Entry(v);
			});
		}
	});
}}}

function rearrangeFeeds(){{{
	var d = $("#rearrangeFeeds");
	var dBody = d.find(".modal-body");
	dBody.find("#categories").empty();
	dBody.find("#entries").empty();

	var sortedFeeds = globalFeeds.slice(0); /* copy the array */
	sortedFeeds.sort(function(a,b){return parseInt(a.data.startID) < parseInt(b.data.startID) ? -1 : 1;}); /* sort by startID ASC */
	/* this sort will leave undefined entries at the end */

	for (var i = 0; i <= sortedFeeds.length; i++){
		var ptr = sortedFeeds[i];
		if (!ptr){
			break;
		}
		if (ptr.isDirectory){
			/* new category */
			var a = $("<a parentid='"+ptr.data.ID+"' href='#'>"+ptr.data.name+"</a>").on("click", function(){
					dBody.find("#entries").find("ul").addClass("hide");
					dBody.find("#entries").find("#parent_"+$(this).attr("parentid")).removeClass("hide");
				});
			var li = $("<li dropid='"+ptr.data.ID+"' id='category_"+ptr.data.ID+"'></li>").append(a);
			li.droppable({
				hoverClass: "ui-droppable-hover",
				greedy: true,
				drop: function(event, ui){
					var droppedOn = $(this);
					var dragged = ui.draggable;
					if (dragged.attr("feedid") == droppedOn.attr("dropid")){
						/* dropped onto itself */
						return;
					};
					if (dBody.find("#entries").find("#parent_"+droppedOn.attr("dropid")).length){
						if (!(dBody.find("#entries").find("#parent_"+droppedOn.attr("dropid")).hasClass("hide"))){
							/* dropped into same category as it is now */
							return;
						}
					}
					dragged.spin();
					$.ajax({
						url: "rest.php/feed/"+dragged.attr("feedid")+"/move",
						type: "POST",
						dataType: "json",
						data: {
							moveIntoCategory: droppedOn.attr("dropid")
						},
						success: function(data) {
							if (data.status != "OK"){
								alert(data.msg);
								return;
							}
							var x = dBody.find("#entries").find("#parent_"+droppedOn.attr("dropid"));
							var n = dragged.next();
							dragged.appendTo(x);
							n.appendTo(x);

							x = dBody.find("#categories").find("#category_"+dragged.attr("feedid"));
							if (x.length){
								/* Moved a category */
								var ul = x.parent();
								ul.appendTo(droppedOn);
							}
							getFeeds();
						},
						complete: function(){ dragged.spin(false); }
					});
				}
			});
			var ul = $("<ul>").append(li);
			var p;
			if (i == 0){
				p = dBody.find("#categories");
			} else {
				p = dBody.find("#category_"+ptr.parent.data.ID);
			}
			p.append(ul);
		}
		if (ptr.parent){
			/* new feed */
			var li = $("<li feedid='"+ptr.data.ID+"'>"+ptr.data.name+"</li>");
			var p = dBody.find("#entries").find("#parent_"+ptr.parent.data.ID);
			if (!p.length){
				p = $("<ul class='hide' id='parent_"+ptr.parent.data.ID+"'>");
				dBody.find("#entries").append(p);
			}
			p.append(li);
			li.draggable({
				revert: true,
				revertDuration: 0
			})
			var lidrop = $("<li dropid='"+ptr.data.ID+"'>&nbsp;</li>");
			lidrop.droppable({
				hoverClass: "ui-droppable-hover",
				drop: function(event, ui){
					var droppedOn = $(this);
					var dragged = ui.draggable;
					if (dragged.attr("feedid") == droppedOn.attr("dropid")){
						return;
					};
					dragged.spin();
					$.ajax({
						url: "rest.php/feed/"+dragged.attr("feedid")+"/move",
						type: "POST",
						dataType: "json",
						data: {
							moveAfterFeed: droppedOn.attr("dropid")
						},
						success: function(data) {
							if (data.status != "OK"){
								alert(data.msg);
								return;
							}
							dragged.next().insertAfter(droppedOn);
							dragged.insertAfter(droppedOn);
							getFeeds();
						},
						complete: function(){ dragged.spin(false); }
					});
				}
			});
			li.after(lidrop);
		}
	}

	d.modal();
}}}
function FeedCollapse(collapse){{{
	var that = this;
	this.folder.toggleClass("icon-folder-open icon-folder-close");
	for (var ptr = this.li.next(); ptr.length; ptr = ptr.next()){
		if (parseInt(ptr.attr("startID")) >= that.data.startID && parseInt(ptr.attr("endID")) <= that.data.endID){
			if (collapse){
				ptr.hide();
			} else {
				ptr.show();
			}
		}
	}
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
	var f = this;

	var d = $("#feedSettings");
	d.find("#name").val(f.data.name);
	d.find("#url").val(f.data.url).attr("disabled", f.isDirectory);
	d.find("#isgroup").unbind("switch-change")
			  .on("switch-change", function(e, data){
				  var value = data.value;
				  d.find("#url").attr("disabled", value == "1");
			  })
	                  .switch("setState", f.isDirectory);
	d.find("#cacheimages").switch("setState", f.data.cacheimages == "yes");
	d.find("#deleteFeed").button().on("click", function(){
		if (confirm("Really delete feed? This cannot be undone!")){
			if (parseInt(f.data.startID) == 1){
				alert("Cowardly refusing to delete root category '"+f.data.name+"'");
			}
			else if (parseInt(f.data.endID) - 1 != parseInt(f.data.startID)){ // non-empty category
				alert("Cowardly refusing to delete non-empty category!");
			} else {
				d.dialog("close");
				f.deleteFeed();
			}
		}
	});

	var filter = d.find("#filter");
	filter.empty().spin("tiny");
	$.ajax({
		url: "rest.php/feed/"+f.data.ID+"/filter",
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

	$("#buttonDeleteFeed").unbind("click").bind("click", function(){ if (confirm("Really delete feed? This cannot be undone!")){ f.deleteFeed(); } });
	$("#buttonSaveChanges").unbind("click");
	$("#buttonSaveChanges").bind("click", function(){ f.updateFeed(); });
	$("#feedSettings").modal();
}}}
function FeedUpdateFeed(){{{
	var f = this;
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
		url: "rest.php/feed/"+f.data.ID,
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
			f.data.name = d.find("#name").val();
			f.data.cacheimages = d.find("input[name=cacheimages]:checked").val();
			f.data.url = d.find("input[name=isgroup]:checked").val() == "1" ? "" : d.find("#url").val();
			f.nameFeed.empty().append(d.find("#name").val());
		},
		complete: function(){
			d.modal("hide");
		}
	});
}}}
function FeedRenderCount(){{{
	var f = this;
	f.numNew.empty();
	if (parseInt(f.data.unreadCount) > 0){
		f.numNew.append(f.data.unreadCount);
		f.li.addClass("new");
	} else {
		f.li.removeClass("new");
	}
}}}
function FeedIsDirectory(){{{
	if ("x"+this.data.url == "x" ||
		"x"+this.data.url == "xnull" ||
		"x"+this.data.url == "xundefined"){
		this.isDirectory=returnTrue;
	} else {
		this.isDirectory=returnFalse;
	}
	return this.isDirectory;
}}}
function FeedGetEntries(today){{{
	var f = this;

	if (curFeed){
		if (curFeed != this){
			delete(curFeed.entries);
		}
	}
	curFeed = this;

	f.spin.spin("tiny");
	$.ajax({
		url: "rest.php/feed/"+f.data.ID+"/entries/"+f.SQLDate(),
		type: "GET",
		dataType: "json",
		success: function(data){
			var e = globalUlEntries;

			$("ul#feeds li.active").removeClass("active");
			f.li.addClass("active");
			if (data.length == 0){
				e.find(".loadMore").remove();
				e.append("<li class='loadMore'>[ No more entries ]</li>");
				return;
			}

			e.append("<li class='nav-header'>"+data[0].date.substr(0, 10)+"</li>");
			var scrollTop = e.scrollTop();
			if (today){
				scrollTop = 0;
				for (var ptr = 0; ptr < globalFeeds.length; ptr++){
					if (globalFeeds[ptr])
						globalFeeds[ptr].entries = new Array();
				}
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
					var d = newDate.match(/^(....)-(..)-(..)/);
					f.date = new Date(parseInt(d[1]), parseInt(d[2])-1, parseInt(d[3])-1);
					f.getEntries(false);
				})
			);
			e.append(li);

			if (today){
				e.scrollTop(0);
			} else {
				e.scroll(scrollTop);
			}
		},
		complete: function(){ f.spin.spin(false); globalUlEntries.parent().spin(false); }
	});
}}}
function FeedMarkAllRead(){{{
	var f = this;
	var maxID;

	if (!(maxID = parseInt(f.data.maxID))){
		maxID = 0;
	}

	f.spin.spin("tiny");
	if (f.entries){
		$.each(f.entries, function(k,v){
			if (!v){
				return true;
			}
			maxID = max(maxID, v.data.ID);
		});
	}

	$.ajax({
		url: "rest.php/feed/"+this.data.ID+"/markAllRead",
		dataType: "json",
		type: "POST",
		data: { maxID: maxID },
		complete: function(){ f.updateCount(); },
		success: function(data){
			if (data.status == "OK"){
				if (curFeed == f){
					globalUlEntries.find("li.new").removeClass("new").find("i.icon-star").toggleClass("icon-star icon-star-empty");
				}
				f.updateCount();
			} else {
				alert(data.msg);
			}
		}
	});
}}}
function FeedUpdateCount(){{{
	var f = this;
	f.spin.spin("tiny");
	$.ajax({
		url: "rest.php/unreadcount/"+f.data.ID,
		dataType: "json",
		type: "GET",
		complete: function(){ f.spin.spin(false); },
		success: function(data){
			$.each(data, function(k, v){
				var feed = globalFeeds[parseInt(v.ID)];
				feed.data.unreadCount = v.unread;
				feed.data.maxID = v.maxID;
				feed.renderCount();
			})
			for (var x = f.parent; x; x=x.parent){
				/* only need to update parents */
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
	var f = this;
	f.spin.spin("tiny");

	$.ajax({
		url: "rest.php/feed/"+f.data.ID,
		type: "DELETE",
		dataType: "json",
		success: function(data){
			f.spin.spin(false);
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
	var f = this;
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
	this.children = new Array();

	if (this.data.startID != "1"){
		var parentFeed = $("li").filter(function(){
			return  $(this).attr("startID") <= parseInt(f.data.startID) &&
				$(this).attr("endID")   >= parseInt(f.data.endID)   &&
			globalFeeds[parseInt($(this).attr("group"))].isDirectory;
		}).last().attr("group");
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

	this.buttons = new Object();
	this.buttons.settings = $("<i class='icon-pencil floatRight editButton' />")
		.on("click", function(){
			f.showSettings();
			return false;
		});
	this.buttons.newMessage = $("<i id='newMessage_"+this.data.startID+"' class='icon-envelope newmessage floatRight' />")
		.on("click", function() {
			f.markAllRead();
			return false;
		});

	this.numNew = $("<a id='numNew_"+this.data.startID+"' class='numNewMessages' />");

	this.nameFeed = $("<a href='#' />")
		.append(this.buttons.settings)
		.append(this.buttons.newMessage)
		.append(this.spin)
		.append($("<span href='#' class='nameFeed'>"+this.data.name+"</span>").append(this.numNew));

	this.li.attr("startID", this.data.startID)
	       .attr("endID",   this.data.endID)
	       .attr("group",   this.data.ID)
				 .append(this.nameFeed);

	$("#feeds").append(this.li);

	var indent = 0;
	for (var ptr = this.parent; ptr; ptr=ptr.parent){
		indent = indent + 16;
	}
	this.li.css("padding-left", indent + "px");

	if (this.isDirectory){
		this.li.addClass("group");
		this.folder = $("<i class='icon-folder-open'></i>");
		this.folder.on("click", function(){
			var open = f.folder.hasClass("icon-folder-open");
			if (open){
				f.collapse(true);
			} else {
				f.collapse(false);
			}
			return false;
		});
		this.li.find(".nameFeed").before(this.folder);
	} else {
		this.li.addClass("feed");
	}
	this.nameFeed.on("click", function(){
		for (var ptr=0; ptr<globalFeeds.length; ptr++){
			if (globalFeeds[ptr])
				globalFeeds[ptr].entries = new Object();
		}
		f.date = new Date();
		globalUlEntries.empty();
		globalUlEntries.append("<li class='nav-header'>Feedentries</li>");
		f.getEntries();
	});
}}}

function EntryMarkRead(){{{
	var e = this;
	if (e.data.isread == "1"){
		return;
	}
	e.toggleRead();
}}}
function EntryToggleRead(){{{
	var e = this;
	if (e.data.isread == "1"){
		e.data.isread = "0";
	} else {
		e.data.isread = "1";
	}
	e.render();
	$.ajax({
		url: "rest.php/entry/"+this.data.ID,
		type: "PUT",
		dataType: "json",
		data: JSON.stringify({ isread: e.data.isread }),
		success: function(data){
			if (data.status == "OK"){
				var f;
				for (f = e.data.feed; f; f=f.parent){
					if (e.data.isread == "0"){
						f.data.unreadCount++;
					} else {
						f.data.unreadCount--;
					}
					f.renderCount();
				}
			} else {
				alert(data.msg);
			}
		}
	});
}}}
function EntryShow(){{{
	var e = this;
	$("#content").empty().spin();
	$("#headline").empty().attr("href", "#").html("Loading...");
	$.ajax({
		url: "rest.php/entry/"+e.data.ID,
		type: "GET",
		dataType: "json",
		success: function(data){
			if (data.status == "error"){
				alert(data.msg);
				return;
			}
			$("#content").html(data.description.replace(/<(\/?)script/, "<$1disabledscript"));
			$("#headline").empty().attr("href", data.link).html("<nobr>"+data.title.replace(/</, "&lt;").replace(/>/, "&gt;")+"</nobr>");
			$("ul#entries li.active").removeClass("active");
			li = $("ul#entries li#entry_"+e.data.ID);
			li.addClass("active");
			e.markRead();
		},
		complete: function(){ $("#content").spin(false); }
	})
}}}
function EntryRender(){{{
	var e = this;
	li = $("ul#entries li#entry_"+e.data.ID);
	if (e.data.isread == "1"){
		li.removeClass("new");
	} else {
		li.addClass("new");
	}
}}}
function EntryUpdate(){{{
	if (this.data.isread == "0"){
		this.li.addClass("new");
	} else {
		this.li.removeClass("new");
	}
	this.iconNew.detach();
	this.iconFav.detach();
	this.iconTag.detach();
	this.a
		.empty()
		.append(this.iconTag)
		.append(this.iconFav)
		.append(this.iconNew)
		.append(this.data.title);
}}}
function Entry(data){{{
	var that = this;
	this.data = data;
	this.show = EntryShow;
	this.markRead = EntryMarkRead;
	this.toggleRead = EntryToggleRead;
	this.render = EntryRender;
	this.update = EntryUpdate;

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
	this.iconTag = $("<i class='floatLeft icon-tags' />");

	if (this.data.favorite == "yes"){
		this.iconFav.toggleClass("icon-star icon-star-empty");
	}

	this.a = $("<a href='#' />")
		.append(this.iconTag)
		.append(this.iconFav)
		.append(this.iconNew)
		.on("click", function(){
			that.show();
		});
	this.li = $("<li id='entry_"+this.data.ID+"' />").append(this.a);

	this.update();
	globalUlEntries.append(this.li);
}}}

function getFeeds(){{{
	var feed_1 = $("#feeds");
	feed_1.find(".group").remove();
	feed_1.find(".feed").remove();
	$.ajax({
		url: "rest.php/feeds",
		type: "GET",
		dataType: "json",
		async: false,
		success: function(data){
			if (!data){
				return;
			}
			/* We're cheating a bit to get the first feed */
			globalFeeds = new Array();
			$.each(data, function(k,v){
				if (k == 0){
					globalRootFeed = new Feed(v);
					return true; // continue; // already initialized in static HTML
				}

				new Feed(v);
			});
			var f;
			for (var ptr = 0; ptr < globalFeeds.length; ptr++){
				if (f = globalFeeds[ptr]){
					if (f.data.collapsed == "yes"){
						f.collapse(true);
					}
				}
			}
		},
	});
}}}
function startup(){{{
	$("input[type=button]").button();
	$(".selectpicker").selectpicker();
	$("#specialFavorites").on("click", showFavorites);
	$("#specialUnread").on("click", showUnread);
	globalUlEntries = $("ul#entries");
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
	var sortedFeeds = globalFeeds.slice(0); /* copy the array */
	sortedFeeds.sort(function(a,b){return parseInt(a.data.startID) < parseInt(b.data.startID) ? -1 : 1;}); /* sort by startID ASC */

	var options = "";
	$.each(sortedFeeds, function(k,v){
		if (v == undefined){
			return true; // continue;
		}
		if (v.isDirectory){
			var s = "";
			for (var x = v; x; x=x.parent){
				s = "/"+x.data.name+s;
			}
			options += "<option value='"+v.data.ID+"'>"+s+"</option>";
		}
	});
	d.find("select#parent").empty().append(options);
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
function getOptions(){{{
	$.ajax({
		url: "rest.php/options",
		type: "GET",
		dataType: "json", 
		success: function(data){
			$("#selectPurgeAfter").val(data.purgeAfter.value)
					      .addClass("selectpicker")
					      .selectpicker();
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
