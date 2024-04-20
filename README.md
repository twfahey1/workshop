# Workshop

This module provides functionality via integration with AI.

## Recommended setup
In your `settings.local.php`, define the configuration:
```
$config['workshop.settings']['openai_api_key'] = 'your-key';
```

## Features
### Scratchpad
This example tool provides a way to quickly get code changes on a snippet of code. It uses the OpenAI API to generate code based on the input code.