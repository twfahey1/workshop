<?php

namespace Drupal\workshop\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a workshop form.
 */
class ScratchPadForm extends FormBase
{


  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'workshop_scratch_pad';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['workarea'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code workspace'),
      '#required' => TRUE,
      '#prefix' => '<div id="workarea">',
      '#suffix' => '</div>',
      '#attributes' => [
        'class' => ['workarea-textarea'] // Additional classes for textarea
      ],
    ];
    
    $form['copy_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Copy to Clipboard'),
      '#attributes' => [
        'class' => ['copy-to-clipboard'],
      ],
    ];
    
    // Add the workshop/copy_to_clipboard lib
    $form['#attached']['library'][] = 'workshop/scratchpad_copy_to_clipboard';


    $form['update_command'] = [
      // textfield
      '#type' => 'textarea',
      '#title' => $this->t('Update command'),
      '#description' => $this->t('The command to run to update the code.'),
      '#default_value' => '',
    ];

    // Select for model to use either 3.5, 4
    $form['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#description' => $this->t('The model to use for generating the response.'),
      '#options' => [
        'gpt-3.5-turbo' => $this->t('gpt-3.5-turbo'),
        'gpt-4' => $this->t('gpt-4'),
        'gpt-4-1106-preview' => $this->t('gpt-4-1106-preview'),
      ],
      '#default_value' => 'gpt-3.5-turbo',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    // An ajax button "Craft" that updateWorkArea ajax
    $form['actions']['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Craft'),
      '#ajax' => [
        'callback' => '::updateWorkArea',
        'wrapper' => 'message',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
  }

  /**
   * {@inheritdoc}
   */
  public function updateWorkArea(array &$form, FormStateInterface $form_state)
  {
    /// Get the form values
    $workarea = $form_state->getValue('workarea');
    $update_command = $form_state->getValue('update_command');
    $model = $form_state->getValue('model');

    $gpt_manager = \Drupal::service('workshop.gpt_manager');
    $prompt = [
      ["role" => "system", "content" => "The code we're working on is:"],
      ["role" => "user", "content" => $workarea],
      ["role" => "system", "content" => "Update the code maintaining original language and style based on the following request: " . $update_command],
    ];
    $gpt_updated_code = $gpt_manager->generateResponse($prompt, $model);
    // Use ajaxresponse for update the textarea code
    $response = new AjaxResponse();
    // Update the $form['workarea'] with the new code.
    // Lets do this by re-rendering the form element.
    // We can use the form_builder service to do this.
    $form['workarea']['#value'] = $gpt_updated_code;
    // Render the markup for the $form element
    $form_element_rendered = \Drupal::service('renderer')->render($form['workarea']);
    $response->addCommand(new ReplaceCommand('#workarea', $form_element_rendered));
    return $response;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
  }

}
