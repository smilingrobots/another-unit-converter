module.exports = function(grunt) {

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    less: {
      production: {
        options: {
          plugins: [
            new (require('less-plugin-autoprefix'))({browsers: ["last 2 versions"]}),
            new (require('less-plugin-clean-css'))({sourceMap: true})
          ]
        },
        files: {
          'another-unit-converter/resources/css/frontend.css': 'another-unit-converter/resources/less/frontend.less'
        }
      }
    },

    watch: {
      less: {
        files: ['another-unit-converter/resources/less/**/*.less'],
        tasks: ['less']
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-less');
  grunt.loadNpmTasks('grunt-contrib-watch');

  grunt.registerTask('default', ['less']);

};
