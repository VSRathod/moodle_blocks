// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A javascript module that handles the change of the user's visibility in the
 * online users block.
 *
 * @module     block_recommendation/view

 */
define(["jquery"], function($) {
    
    $('.recommend_slider').slick({
    dots: false,
      arrows: true,
      infinite: false,
      speed: 500,
       slidesToShow: 4,
      slidesToScroll: 1,
      responsive: [{
          breakpoint: 2760,
          settings: {
            slidesToShow: 5,
            slidesToScroll: 1
          }
        },{
          breakpoint: 1700,
          settings: {
            slidesToShow: 4,
            slidesToScroll: 1
          }
        },{
          breakpoint: 1300,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 1
          }
        },{
          breakpoint: 800,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 2
          }
        }, {
          breakpoint: 480,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1
          }
        }
      ]
    }); 
});

 

