# DALL-E AI Image Generation Setup

This guide explains how to set up the DALL-E AI image generation feature in your PODQR application.

## Prerequisites

1. An OpenAI account with API access
2. DALL-E 3 API access (requires OpenAI API credits)

## Setup Instructions

### 1. Get OpenAI API Key

1. Go to [OpenAI Platform](https://platform.openai.com/)
2. Sign in or create an account
3. Navigate to "API Keys" section
4. Create a new API key
5. Copy the API key (it starts with `sk-`)

### 2. Configure Environment Variables

Add the following line to your `.env` file:

```env
OPENAI_API_KEY=sk-your-api-key-here
```

### 3. Verify Configuration

1. Clear your Laravel configuration cache:
   ```bash
   php artisan config:clear
   ```

2. Test the configuration by visiting the T-shirt designer page and clicking the "AI Art" button.

## Usage

### For Users

1. Go to the T-shirt designer page
2. Click the "AI Art" button in the toolbar
3. Enter a detailed description of the image you want to generate
4. Select image size and quality options
5. Click "Generate Image"
6. Once generated, click "Add to Design" to place it on your t-shirt

### Tips for Better Results

- Be specific and descriptive in your prompts
- Include style preferences (e.g., "digital art", "watercolor", "cartoon")
- Mention colors and mood
- Specify the subject clearly

### Example Prompts

- "A cute cartoon cat wearing a superhero cape, digital art style, vibrant colors"
- "A minimalist geometric pattern in blue and white, modern design"
- "A vintage-style flower illustration, watercolor technique, soft pastel colors"

## Troubleshooting

### Common Issues

1. **"AI image generation is not configured"**
   - Check that `OPENAI_API_KEY` is set in your `.env` file
   - Clear config cache: `php artisan config:clear`

2. **"Content policy violation"**
   - Your prompt contains content that violates OpenAI's policies
   - Try a different, more appropriate description

3. **"Rate limit exceeded"**
   - Too many requests in a short time
   - Wait a few minutes and try again

4. **"Billing issue"**
   - Your OpenAI account needs more credits
   - Add credits to your OpenAI account

### Cost Considerations

- DALL-E 3 costs approximately $0.04 per image (1024x1024, standard quality)
- HD quality costs approximately $0.08 per image
- Monitor your OpenAI usage to control costs

## Security Notes

- Never commit your API key to version control
- Keep your `.env` file secure
- Consider implementing rate limiting for production use
- Monitor API usage to prevent abuse

## Support

If you encounter issues:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify your OpenAI API key is valid
3. Ensure you have sufficient OpenAI credits
4. Contact support if problems persist 