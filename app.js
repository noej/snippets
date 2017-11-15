/*
 * @author Noel Jarencio
 * @description An app that gathers twitter sentiments for specific topics 
 */
var express = require('express');
var path = require('path');
var favicon = require('serve-favicon');
var logger = require('morgan');
var cookieParser = require('cookie-parser');
var bodyParser = require('body-parser');
var hod = require('havenondemand')
var db = require('./models/db');

var app = express();

hodClient = new hod.HODClient('http://api.idolondemand.com', process.env.IDOL_API)

// uncomment after placing your favicon in /public
//app.use(favicon(path.join(__dirname, 'public', 'favicon.ico')));
app.use(logger('dev'));
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: false }));
app.use(cookieParser());

// catch 404 and forward to error handler
app.use(function(req, res, next) {
    var err = new Error('Not Found');
    err.status = 404;
    next(err);
});

// error handlers

// development error handler
// will print stacktrace
if (app.get('env') === 'development') {
    app.use(function(err, req, res, next) {
        res.status(err.status || 500);
        res.render('error', {
            message: err.message,
            error: err
        });
    });
}

// production error handler
// no stacktraces leaked to user
app.use(function(err, req, res, next) {
    res.status(err.status || 500);
    res.render('error', {
        message: err.message,
        error: {}
    });
});

function processMentions(processAll) {
    var processAll = processAll || false;

    var Candidate = require('../models/candidate');
    var Mention = require('../models/mention');

    Candidate.find( function(err, candidates) {
        if( err ) res.send( err );

        var keyword = '';
        var now = '';
        var startDate = '';
        var endDate = '';
        var mention = null;

        for( i = 0; i < candidates.length; i++ ) {
            keyword = new RegExp(candidates[i].track_keywords.replace(/, /gi, '|', 'ig'), 'i');

            if( !processAll ) {
                // Count todays tweets only
                now = Date();
                startDate = now.getFullYear() + (now.getMonth() +1) + now.getDate() + 'T00:00:00.000Z';
                endDate = now.getFullYear() + (now.getMonth() +1) + now.getDate() + 'T23:59:59.000Z';

                Tweets.count({ 
                    text: keyword, 
                    created_at: {
                        $gte: ISODate(startDate), 
                        $lte: ISODate(endDate)
                    }
                }, function(err, total){
                    Mention.findOne({ 
                        candidate_id: candidates[i]._id,
                        date: {
                            $gte: ISODate(startDate), 
                            $lte: ISODate(endDate)
                        }
                    }, function(err, mention) {
                        if( mention == null ) {
                            mention = new Mention(
                                candidate_id: candidates[i]._id,
                                count: total
                            );
                        } else {
                            mention.count = total;
                            mention.date = new Date();
                        }

                        mention.save();
                    });
                });
            }
        }
    });
}

function analyzeSentiments() {
    var Tweet = require('../models/tweet');

    Tweet.find({ sentiment: '' }, function(err, tweets) {
        if( err ) res.send( err );

        for( i = 0; i < tweets.length; i++ ) {
            hodClient.call('analyzesentiment', function(err, resp, body){
                tweets[i].sentiment = body.aggregate.sentiment.toUpperCase();
                tweets[i].save();
            }, {'text' : text})
        }
    });
}

function processSentiments(processAll) {
    var processAll = processAll || false;

    var Candidate = require('../models/candidate');
    var Sentiment = require('../models/sentiment');

    Candidate.find( function(err, candidates) {
        if( err ) res.send( err );

        var keyword = '';
        var now = '';
        var startDate = '';
        var endDate = '';
        var sentiment = null;

        for( i = 0; i < candidates.length; i++ ) {
            keyword = new RegExp(candidates[i].track_keywords.replace(/, /gi, '|', 'ig'), 'i');

            if( !processAll ) {
                // Count todays tweets only
                now = Date();
                startDate = now.getFullYear() + (now.getMonth() +1) + now.getDate() + 'T00:00:00.000Z';
                endDate = now.getFullYear() + (now.getMonth() +1) + now.getDate() + 'T23:59:59.000Z';

                Tweets.count({ 
                    text: keyword, 
                    sentiment: 'NEGATIVE',
                    created_at: {
                        $gte: ISODate(startDate), 
                        $lte: ISODate(endDate)
                    }
                }, function(err, total){
                    Sentiment.findOne({ 
                        candidate_id: candidates[i]._id,
                        date: {
                            $gte: ISODate(startDate), 
                            $lte: ISODate(endDate)
                        }
                    }, function(err, sentiment) {
                        if( sentiment == null ) {
                            sentiment = new Sentiment(
                                candidate_id: candidates[i]._id,
                                count: total
                            );
                        } else {
                            mention.count = total;
                            mention.date = new Date();
                        }

                        mention.save();
                    });
                });
            }
        }
    });
}

module.exports = app;
