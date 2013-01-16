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
	$("#navigation").height(window.innerHeight-($("#navigation").position().top + 40));
	$("#content").height(window.innerHeight-($("#content").position().top + 60));
}}}
window.onresize=resize;

function FeedShowSettings(){{{
	var f = this;
	var d = $("<div />").dialog({
		width: "800px",
		modal: true,
		title: f.data.name,
		close: function(event, ui){
			$(this).remove();
			return true;
		},
		buttons: {
			"Commit": function() {
				d.find("input[type=button]").attr("disabled", true);
				var filter = new Array();
				$.each(d.find("[name^=regex_]"), function(k,v){
					var id = v.name.split("_")[1];
					var regex = v.value;
					var wob = d.find("input[name=whiteorblack_"+id+"]:checked").val();
					var del = d.find("input[name=delete_"+id+"]").attr("checked") == "checked" ? "true" : "false";
					filter[filter.length] = {ID: id, regex: regex, whiteorblack: wob, delete: del};
				});
				$.ajax({
					url: "rest.php/feed/"+f.data.ID,
					type: "PUT",
					dataType: "json",
					data: JSON.stringify({
						name: d.find("#name").val(),
						url: (d.find("input[name=isgroup]:checked").val() == "1" ? "" : d.find("#url").val()),
						cacheimages: d.find("input[name=cacheimages]:checked").val(),
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
						d.dialog("close");
					}
				});
			},
			"Cancel": function(){ d.dialog("close"); }
		}
	});
	d.html("<table>"+
		"<tr><td>Name:</td><td><input type='text' size='60' id='name'></td></tr>"+
		"<tr><td>Feed URL:</td><td><input type='text' id='url' size='60'></td></tr>"+
		"<tr><td>Is a group:</td><td><input type='radio' id='isgroupno' name='isgroup' value='0'><label for='isgroupno'>No</label> <input type='radio' id='isgroupyes' name='isgroup' value='1'><label for='isgroupyes'>Yes</label></td></tr>"+
		"<tr><td>Cache images:</td><td><input type='radio' id='cacheimagesno' name='cacheimages' value='no'><label for='cacheimagesno'>No</label> <input type='radio' id='cacheimagesyes' name='cacheimages' value='yes'><label for='cacheimagesyes'>Yes</label></td></tr>"+
		"<tr><td>Filter:</td><td><table id='filter'></table></td></tr>"+
		"<tr><td>Delete Feed:</td><td><a id='deleteFeed' href='#'>Delete Feed</a></td></tr>"+
		"</table>");

	d.find("#name").val(f.data.name);
	d.find("#url").val(f.data.url).attr("disabled", f.isDirectory);
	d.find("#isgroupyes")
		.attr("checked", f.isDirectory)
		.on("change", function(){ d.find("#url").attr("disabled", $(this).attr("checked")); });
	d.find("#isgroupno")
		.attr("checked", !f.isDirectory)
		.on("change", function(){ d.find("#url").attr("disabled", !$(this).attr("checked")); });
	d.find("#cacheimagesyes").attr("checked", f.data.cacheimages == "yes");
	d.find("#cacheimagesno").attr("checked", f.data.cacheimages == "no");
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
	filter.spin();
	$.ajax({
		url: "rest.php/feed/"+f.data.ID+"/filter",
		type: "GET",
		dataType: "json",
		success: function (data){
			var s = "";
			data[data.length] = {ID: 0, regex: "", whiteorblack: "white"};
			$.each(data, function(k,v){
				s += "<tr><td>"+
					"<input type='radio' value='white' id='white_"+v.ID+"' name='whiteorblack_"+v.ID+"' "+(v.whiteorblack == "white" ? "checked" : "")+">"+
					"<label for='white_"+v.ID+"'>Whitelist</label>"+
					"<input type='radio' value='black' id='black_"+v.ID+"' name='whiteorblack_"+v.ID+"' "+(v.whiteorblack == "black" ? "checked" : "")+">"+
					"<label for='black_"+v.ID+"'>Blacklist</label>"+
					"</td><td>"+
					"<input type='text' value='"+v.regex+"' name='regex_"+v.ID+"'>"+
					"</td><td>"+
					"<input type='checkbox' name='delete_"+v.ID+"' id='delete_"+v.ID+"'><label for='delete_"+v.ID+"'>Delete?</label>"+
					"</td></tr>";
			});
			filter.append(s).spin(false);
		},
		complete: function(){ d.find("input[type=radio]").button(); d.find("input[type=checkbox]").button(); }
	});
}}}
function FeedRenderCount(){{{
	var f = this;
	f.numNew.empty();
	if (parseInt(f.data.unreadCount) > 0){
		f.numNew.append(f.data.unreadCount);
		f.ul.addClass("new");
	} else {
		f.ul.removeClass("new");
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
function FeedGetEntries(){{{
	var f = this;

	if (curFeed){
		if (curFeed != this){
			delete(curFeed.entries);
		}
	}
	curFeed = this;

	f.spin.spin("tiny");
	$.ajax({
		url: "rest.php/feed/"+f.data.ID+"/entries/"+f.entry,
		type: "GET",
		dataType: "json",
		success: function(data){
			var e = globalUlEntries;
			$("ul.feed li.active").toggleClass("inactive active");
			f.li.toggleClass("inactive active");
			if (data.length == 0){
				e.find(".loadMore").remove();
				e.append("<li class='loadMore inactive'>[ No more entries ]</li>");
				return;
			}
			var scrollTop = e.parent().scrollTop();
			if (f.entry == 0){
				e.empty();
				e.append("<li class='nav-header'>Feedentries</li>");
				scrollTop = 0;
				f.entries = new Array();
			} else {
				e.find(".loadMore").remove();
			}
			$.each(data, function(k, v){
				var li = $("<li class='inactive' id='entry_"+v.ID+"' />");
				if (v.isread == "0"){
					li.addClass("new");
				}
				v.feed = f;
				f.entries[v.ID] = new Entry(v);
				li.append(
					$("<a href='#'></a>")
					.append(v.title)
					.on("click", function(){
						f.entries[v.ID].show();
					})
				);
				e.append(li);
			});
			var li = $("<li class='loadMore inactive' />");
			li.append(
				$("<a href='#'>[ Load 25 more entries ]</a>")
				.on("click", function(){
					f.entry += 25;
					f.getEntries();
				})
			);
			e.append(li);
			if (f.entry == 0){
				e.parent().scrollTop(0);
			} else {
				e.parent().scroll(scrollTop);
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
				globalUlEntries.find("li").removeClass("new");
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
function FeedGetDetails(){{{
	var f = this;
	if (!f.isDirectory){
		alert("getDetails called on non-directory element!");
	}
	f.spin.spin("tiny");
	$.ajax({
		url: "rest.php/feed/"+this.data.ID+"/children.json",
		type: "GET",
		dataType: "json",
		success: function(data){
			var skipUntil = parseInt(f.data.startID);
			f.children = new Array();
			$.each(data, function(k,v){
				if (parseInt(v.startID) < skipUntil){
					return true; // continue
				}
				if ($("#feed_"+v.startID).length){
					return true; // continue
				}

				f.ul.append("<li><ul id='feed_"+v.startID+"' /></li>");
				var nF = new Feed(v);
				nF.parent = f;
				f.children[f.children.length] = nF;

				if (nF.isDirectory){
					skipUntil = max(parseInt(skipUntil), parseInt(v.endID));
					nF.getDetails();
				}
			});
		},
		complete: function(){ f.spin.spin(false); }
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
			f.ul.parent().remove();
		}
	});
}}}
function Feed(data){{{
	var f = this;
	globalFeeds[parseInt(data.ID)] = this;
	this.data = data;
	this.getDetails=FeedGetDetails;
	this.getEntries=FeedGetEntries;
	this.updateCount=FeedUpdateCount;
	this.markAllRead=FeedMarkAllRead;
	this.renderCount=FeedRenderCount;
	this.showSettings=FeedShowSettings;
	this.deleteFeed=FeedDeleteFeed;

	/* unfortunately, any of these tend to be sent from the server */
	if ("x"+this.data.url == "x" ||
		"x"+this.data.url == "xnull" ||
		"x"+this.data.url == "xundefined"){
		this.isDirectory=true;
	} else {
		this.isDirectory=false;
	}

	this.ul = $("#feed_"+this.data.startID);
	this.li = $("<li id='details_"+this.data.startID+"' class='inactive' />");
	this.spin = $("<span class='floatLeft spin' id='spinFeed_"+this.data.startID+"'>&nbsp;</span>");
	this.nameFeed = $("<a class='nameFeed'>"+this.data.name+"</a>");
	this.numNew = $("<a id='numNew_"+this.data.startID+"' class='numNewMessages' />");

	this.buttons = new Object();
	this.buttons.settings = $("<a class='floatRight icon-pencil' href='#'></a>")
		.on("click", function(){
			f.showSettings();
		});
	this.buttons.newMessage = $("<a id='newMessage_"+this.data.startID+"' class='newmessage floatRight icon-envelope' href='#'></a>")
		.on("click", function() {
			f.markAllRead();
		});

	this.li.append(
		$("<div class='floatRight'>").append(this.buttons.settings)
		.append(this.buttons.newMessage)
		.append(this.spin)
	);
	this.li.append(
		$("<div class='nameFeed'>").append(this.nameFeed)
		.append(this.numNew)
	);

	this.ul.attr("startID", this.data.startID)
	       .attr("endID",   this.data.endID)
	       .attr("group",   this.data.ID)
	       .append(this.li);
	if (this.isDirectory){
		this.ul.addClass("group");
	} else {
		this.ul.addClass("feed");
		this.nameFeed.parent().on("click", function(){
			f.entry = 0;
			f.getEntries();
		});
	}
}}}

function EntryMarkRead(){{{
	var e = this;
	if (e.data.isread == "1"){
		return;
	}
	e.data.isread = "1";
	$.ajax({
		url: "rest.php/entry/"+this.data.ID,
		type: "PUT",
		dataType: "json",
		data: JSON.stringify({ isread: 1 }),
		success: function(data){
			if (data.status == "OK"){
				$("#entry_"+e.data.ID).removeClass("new");
				var f;
				for (f = e.data.feed; f; f=f.parent){
					f.data.unreadCount--;
					f.renderCount();
				}
			} else {
				alert(data.msg);
			}
		}
	});
}}}
function EntryShow(){{{
	$("#headline").empty().attr("href", this.data.link).html(this.data.title.replace(/</, "&lt;").replace(/>/, "&gt;"));
	$("#content").empty().html(this.data.description.replace(/<(\/?)script/, "<$1disabledscript"));
	$("ul#entries li.active").toggleClass("inactive active");
	$("ul#entries li#entry_"+this.data.ID).toggleClass("inactive active");

	this.markRead();
}}}
function Entry(data){{{
	this.data = data;
	this.show = EntryShow;
	this.markRead = EntryMarkRead;
}}}

function getFeeds(){{{
	var feed_1 = $("#feed_1");
	//feed_1.empty();
	$(".sidebar-nav").spin();
	$.ajax({
		url: "rest.php/feeds",
		type: "GET",
		dataType: "json",
		success: function(data){
			if (!data){
				return;
			}
			/* We're cheating a bit to get the first feed */
			globalFeeds = new Array();
			globalRootFeed = new Feed(data[0]);
			globalRootFeed.getDetails();
		},
		complete: function(){ $(".sidebar-nav").spin(false); }
	});
}}}
function startup(){{{
	$("input[type=button]").button();
	globalUlEntries = $("ul#entries");
	resize();
	getFeeds();
}}}
$(document).ready(startup);

function showAddFeed(){{{
	var f = this;
	var d = $("<div />").dialog({
		width: "800px",
		modal: true,
		title: "Add Feed",
		close: function(event, ui){
			$(this).remove();
			return true;
		},
		buttons: {
			"FF Handler": addRSSHandler,
			"Commit": function(){ d.dialog("close"); },
			"Cancel": function(){ d.dialog("close"); }
		}
	});
	d.html("<table>"+
		"<tr><td>Parent:</td><td><select id='parent' /></td></tr>"+
		"<tr><td>Name:</td><td><input type='text' size='60' id='name'></td></tr>"+
		"<tr><td>Feed URL:</td><td><input type='text' id='url' size='60'></td></tr>"+
		"<tr><td>Is a group:</td><td id='isGroupYesNo'><input type='radio' id='isgroupno' name='isgroup' checked value='0'><label for='isgroupno'>No</label><input type='radio' id='isgroupyes' name='isgroup' value='1'><label for='isgroupyes'>Yes</label></td></tr>"+
		"<tr><td>Cache images:</td><td id='cacheImagesYesNo'><input type='radio' id='cacheimagesno' name='cacheimages' checked value='no'><label for='cacheimagesno'>No</label><input type='radio' id='cacheimagesyes' name='cacheimages' value='yes'><label for='cacheimagesyes'>Yes</label></td></tr>"+
		"</table>");
	d.find("#isGroupYesNo").buttonset();
	d.find("#cacheImagesYesNo").buttonset();
	d.find("#isgroupyes")
		.on("change", function(){ d.find("#url").attr("disabled", $(this).attr("checked")); });
	d.find("#isgroupno")
		.on("change", function(){ d.find("#url").attr("disabled", !$(this).attr("checked")); });
	var options = "";
	$.each(globalFeeds, function(k,v){
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
	d.find("select#parent").append(options);
}}}
function addRSSHandler(){{{
	var addDir = $("#parent :selected");
	if (confirm("Add Feed handler to Firefox and place new feeds under "+addDir.text()+"?")){
		var l = window.location;
		var address = l.protocol+"/"+l.hostname+l.pathname.replace("index.html", "")+"add.php?url=%s&parentID="+addDir.val();
		window.navigator.registerContentHandler('application/vnd.mozilla.maybe.feed',address,"blindRSS " + addDir.text().replace(/^.*\//, ""));
	}
}}}
