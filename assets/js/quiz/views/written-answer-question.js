/**
 * Written answer question view.
 */
(function(exports, $) {
	'use strict';

	var WrittenAnswerQuestionView = EdrQuiz.QuestionView.extend({
		/** @member {string} */
		className: 'edr-question edr-question_written-answer',

		/** @member {Function} */
		template: _.template($('#edr-tpl-writtenanswerquestion').html()),

		/**
		 * Initialize view.
		 */
		initialize: function() {
			EdrQuiz.QuestionView.prototype.initialize.apply(this);
		},

		/**
		 * Render view.
		 */
		render: function() {
			EdrQuiz.QuestionView.prototype.render.apply(this);
		},

		/**
		 * Save the question.
		 *
		 * @param {Object} e
		 */
		saveQuestion: function(e) {
			var that = this;
			var newData = {};

			EdrQuiz.QuestionView.prototype.saveQuestion.apply(this);

			// Setup question data.
			newData.question = this.$el.find('.question-text').val();
			newData.question_content = this.$el.find('.question-content').val();
			newData.optional = this.$el.find('.question-optional').prop('checked') ? 1 : 0;
			newData.menu_order = this.$el.index();

			// Send request to the server.
			this.model.save(newData, {
				wait: true,
				success: function(model, response, options) {
					if (response.status === 'success') {
						// If question was new, get id from the server
						if (model.isNew()) {
							model.set('id', parseInt(response.id, 10));
						}
					}
				},
				error: function(model, xhr, options) {
					that.showMessage('error', 800);
				}
			});

			e.preventDefault();
		}
	});

	exports.WrittenAnswerQuestionView = WrittenAnswerQuestionView;
}(EdrQuiz, jQuery));
