<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface AiCommerceConstants
{
    /**
     * Configuration key for the general-purpose agent system prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_GENERAL_PURPOSE_SYSTEM_PROMPT = 'ai_commerce:backoffice_assistant:system_prompts:general_purpose_system_prompt';

    /**
     * Configuration key for the order management agent system prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_ORDER_MANAGEMENT_SYSTEM_PROMPT = 'ai_commerce:backoffice_assistant:system_prompts:order_management_system_prompt';

    /**
     * Configuration key for the discount management agent system prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_DISCOUNT_MANAGEMENT_SYSTEM_PROMPT = 'ai_commerce:backoffice_assistant:system_prompts:discount_management_system_prompt';

    /**
     * Configuration key for the form fill agent system prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_FORM_FILL_SYSTEM_PROMPT = 'ai_commerce:backoffice_assistant:system_prompts:form_fill_system_prompt';

    /**
     * Configuration key for the Smart CMS system prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_CMS_SYSTEM_PROMPT = 'ai_commerce:smart_cms:system_prompts:system_prompt';

    /**
     * Configuration key for the Smart PIM content improver prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_CONTENT_IMPROVER_PROMPT = 'ai_commerce:smart_pim:system_prompts:content_improver_prompt';

    /**
     * Configuration key for the Smart PIM translation prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_TRANSLATION_PROMPT = 'ai_commerce:smart_pim:system_prompts:translation_prompt';

    /**
     * Configuration key for the Smart PIM translation collection prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_TRANSLATION_COLLECTION_PROMPT = 'ai_commerce:smart_pim:system_prompts:translation_collection_prompt';

    /**
     * Configuration key for the Smart PIM category suggestion prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_CATEGORY_SUGGESTION_PROMPT = 'ai_commerce:smart_pim:system_prompts:category_suggestion_prompt';

    /**
     * Configuration key for the Smart PIM image alt text prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_IMAGE_ALT_TEXT_PROMPT = 'ai_commerce:smart_pim:system_prompts:image_alt_text_prompt';

    /**
     * Configuration key for the Search by Image search term prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SEARCH_BY_IMAGE_PROMPT = 'ai_commerce:search_by_image:system_prompts:search_term_prompt';

    /**
     * Configuration key for the Quick Order image recognition prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_QUICK_ORDER_IMAGE_RECOGNITION_PROMPT = 'ai_commerce:quick_order:system_prompts:image_recognition_prompt';

    /**
     * Specification:
     * - AWS region of the Amazon Bedrock knowledge base, for example `eu-central-1`.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_AWS_REGION = 'AI_COMMERCE:BEDROCK_KNOWLEDGE_BASE_AWS_REGION';

    /**
     * Specification:
     * - ID of the Amazon Bedrock knowledge base. The knowledge base is a precondition of the environment: the knowledge store setup does not create it.
     * - The search needs it. The indexing needs it together with `BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_IDS`.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_ID = 'AI_COMMERCE:BEDROCK_KNOWLEDGE_BASE_ID';

    /**
     * Specification:
     * - IDs of the custom data sources of `BEDROCK_KNOWLEDGE_BASE_ID`, keyed by knowledge document source type, for example `['product_attachment' => 'ABCDE12345']`.
     * - The indexing writes the documents of a source type to its data source. Several source types can use the same data source.
     * - The search does not use it: the search covers all data sources of the knowledge base and filters by source type.
     * - The data sources are a precondition, like the knowledge base: the knowledge store setup does not create them.
     * - Without a data source for a registered source type, the knowledge store setup fails and the indexing of the documents of that source type fails.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_IDS = 'AI_COMMERCE:BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_IDS';

    /**
     * Specification:
     * - Reranking model type of the knowledge base search: `MANAGED`, `CUSTOM` or `NONE`.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_RERANKING_MODEL_TYPE = 'AI_COMMERCE:BEDROCK_KNOWLEDGE_BASE_RERANKING_MODEL_TYPE';

    /**
     * Specification:
     * - ARN of the Amazon Bedrock reranking model for the reranking model type `CUSTOM`, for example `arn:aws:bedrock:eu-central-1::foundation-model/cohere.rerank-v3-5:0`.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_RERANKING_MODEL_ARN = 'AI_COMMERCE:BEDROCK_KNOWLEDGE_BASE_RERANKING_MODEL_ARN';

    /**
     * Specification:
     * - AWS access key ID for a local environment without an IAM role. Set it only in `config_local.php`.
     * - When it is empty, the AWS SDK uses its default credential provider chain.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_AWS_ACCESS_KEY_ID = 'AI_COMMERCE:BEDROCK_KNOWLEDGE_BASE_AWS_ACCESS_KEY_ID';

    /**
     * Specification:
     * - AWS secret access key of `BEDROCK_KNOWLEDGE_BASE_AWS_ACCESS_KEY_ID`. Set it only in `config_local.php`.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_AWS_SECRET_ACCESS_KEY = 'AI_COMMERCE:BEDROCK_KNOWLEDGE_BASE_AWS_SECRET_ACCESS_KEY';

    /**
     * Specification:
     * - AWS session token of temporary credentials, for example from `aws configure export-credentials`. Set it only in `config_local.php`.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_AWS_SESSION_TOKEN = 'AI_COMMERCE:BEDROCK_KNOWLEDGE_BASE_AWS_SESSION_TOKEN';
}
