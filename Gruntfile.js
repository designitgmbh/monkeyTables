//Gruntfile
module.exports = function(grunt) 
{
    //Initializing the configuration object
    grunt.initConfig({
        // Task configuration

        // Task configuration
        uglify: {
            options: {
                preserveComments: false
            },
            monkeyTables: {
                files: {
                    'js/app.min.js': [
                        'js/prism.js',
                        'js/jquery.min.js',
                        'js/bootstrap.min.js',
                        'js/flexslider.js',
                        'js/jquery.backstretch.min.js',
                        'js/jquery.nav.js',
                        'js/jquery.appear.js',
                        'js/jquery.countTo.js',
                        'js/jquery.mixitup.min.js',
                        'js/owl.carousel.min.js',
                        'js/jquery.validation.min.js',
                        'js/respond.js',
                        'js/main.js'
                    ]
                }
            }
        },

        cssmin: {
            options: {
                shorthandCompacting: false,
                roundingPrecision: -1
            },
            monkeyTables: {
                files: {
                    'css/style.min.css': [
                        'css/bootstrap.min.css',
                        'css/animate.css',
                        'css/flexslider.css',
                        'css/owl.carousel.css',
                        'css/prism.css',
                        'css/style.css',
                        'css/labsstyle.css',
                        'css/colors/black.css'
                    ]
                }
            }
        },

        processhtml: {
            options: {
                recursive: true,
                process: true,
                data: {
                  releaseSize: 'xx kB',
                  releaseVersion: 'alpha',
                  releaseDate: '18.11.2015'
                }
            },
            monkeyTables: {
                files: {
                    'index.html': ['monkeytables.html'],
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-processhtml');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');

    // Task definition
    grunt.registerTask('default', ['uglify', 'cssmin', 'processhtml']);
};