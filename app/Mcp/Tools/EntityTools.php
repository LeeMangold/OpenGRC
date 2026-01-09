<?php

namespace App\Mcp\Tools;

/**
 * Entity Management Tools
 *
 * All entity management tools are defined here as minimal shell classes.
 * The base class automatically derives name, description, and entity type
 * from the class name using convention over configuration.
 *
 * Tools with custom behavior (like ManagePolicyTool with auto-code generation)
 * are defined in their own files.
 */

class ManageApplicationTool extends BaseManageEntityTool {}
class ManageAssetTool extends BaseManageEntityTool {}
class ManageAuditTool extends BaseManageEntityTool {}
class ManageAuditItemTool extends BaseManageEntityTool {}
class ManageControlTool extends BaseManageEntityTool {}
class ManageImplementationTool extends BaseManageEntityTool {}
class ManageProgramTool extends BaseManageEntityTool {}
class ManageRiskTool extends BaseManageEntityTool {}
class ManageStandardTool extends BaseManageEntityTool {}
class ManageVendorTool extends BaseManageEntityTool {}
