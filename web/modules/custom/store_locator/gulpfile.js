var gulp = require( 'gulp-help' )( require( 'gulp' ) );

// Gulp / Node utilities
var u = require( 'gulp-util' );
var log = u.log;
var c = u.colors;

// Basic workflow plugins
var prefix = require( 'gulp-autoprefixer' );
var sass = require( 'gulp-sass' );


// -----------------------------------------------------------------------------
// Sass Task
//
// Compiles Sass and runs the CSS through autoprefixer. A separate task will
// combine the compiled CSS with vendor files and minify the aggregate.
// -----------------------------------------------------------------------------

function logError( err, res ) {
	log( c.red( 'Sass failed to compile' ) );
	log( c.red( '> ' ) + err.file.split( '/' )[err.file.split( '/' ).length - 1] + ' ' + c.underline( 'line ' + err.line ) + ': ' + err.message );
}


gulp.task( 'styles', 'Compiles main css files (ie. style.css editor-style.css)', function() {

	return gulp.src( 'scss/**/*.scss' )
	           .pipe( sass({ style: 'compact' }).on( 'error', logError ) )
	           .pipe( prefix() )
	           .pipe( gulp.dest( 'css' ) );
} );

function logError (error) {
	console.log(error.toString());
	this.emit('end');
}

gulp.task( 'server', 'Compile scripts and styles for production purposes', ['styles', 'scripts'], function() {
	console.log( 'The styles and scripts have been compiled for production! Go and clear the caches!' );
} );


// -----------------------------------------------------------------------------
// Watch tasks
//
// These tasks are run whenever a file is saved. Don't confuse the files being
// watched (gulp.watch blobs in this task) with the files actually operated on
// by the gulp.src blobs in each individual task.
//
// A few of the performance-related tasks are excluded because they can take a
// bit of time to run and don't need to happen on every file change. If you want
// to run those tasks more frequently, set up a new watch task here.
// -----------------------------------------------------------------------------
gulp.task( 'watch', 'Watch for changes to various files and process them', function() {
	gulp.watch( 'scss/**/*.scss', ['styles'] );
} );

// -----------------------------------------------------------------------------
// Default: load task listing
//
// Instead of launching some unspecified build process when someone innocently
// types `gulp` into the command line, we provide a task listing so they know
// what options they have without digging into the file.
// -----------------------------------------------------------------------------
gulp.task( 'default', false, ['help'] );
