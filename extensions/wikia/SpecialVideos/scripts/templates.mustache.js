define( 'specialVideos.templates.mustache', [], function() { 'use strict'; return {
    "index" : '<div id="special-videos"><div class="filter"><a href="#" class="active">{{sortingOptions.trend}}</a><!--leave this comment in to truncate whitespace between elementsfor display: inline-block--><a href="#">{{sortingOptions.recent}}</a></div>{{#message}}<p class="message">{{message}}</p>{{/message}}<ul class="video-list mobile">{{#videos}}<li class="item"><div class="thumbnail">{{{thumbnail}}}</div><!--leave this comment in to truncate whitespace between elementsfor display: inline-block--><div class="info"><span class="title">{{title}}</span><span class="views">{{viewTotal}} </span></div></li>{{/videos}}</ul>{{#loadMore}}<button class="btn load-more">{{loadMore}}</button>{{/loadMore}}</div>',
    "video" : '<li class="item"><div class="thumbnail">{{{thumbnail}}}</div><!--leave this comment in to truncate whitespace between elementsfor display: inline-block--><div class="info"><span class="title">{{title}}</span><span class="views">{{viewTotal}} </span></div></li>',
    "done": "true"
  }; });