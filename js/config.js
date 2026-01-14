/**
 * 梦之缘创业投资平台 - 统一配置中心
 * @version 3.0
 * @description 集中管理所有系统配置，避免重复定义
 */

(function(global) {
    'use strict';

    // 系统核心配置
    const SYSTEM_CONFIG = Object.freeze({
        // 基础配置
        PLATFORM_NAME: '梦之缘创业投资平台',
        VERSION: '3.0.0',
        CURRENCY: 'USD',
        CURRENCY_SYMBOL: '$',
        API_BASE_URL: 'http://localhost:8000/api', // Laravel API地址
        
        // 用户配置
        DIRECT_REFERRAL_LIMIT: 5,
        MAX_LEVEL_DEPTH: 7,
        INVITE_CODES_PER_USER: 5,
        
        // 激活配置
        ACTIVATION_AMOUNT: 300,
        
        // 七级分润比例
        PROFIT_RATES: Object.freeze([0.20, 0.08, 0.08, 0.06, 0.05, 0.05, 0.05]),
        TOTAL_PROFIT_RATE: 0.57,
        
        // 资金池配置
        POOL_CONFIG: Object.freeze({
            TOTAL_DIVIDEND: 0.33,
            ENTREPRENEUR_POOL: 0.30,
            CHARITY_POOL: 0.03,
            PLATFORM_RETENTION: 0.10,
            REMAINING_POOL: 0.24
        }),
        
        // 安全配置
        SECURITY: Object.freeze({
            MAX_LOGIN_ATTEMPTS: 5,
            LOCKOUT_DURATION: 30 * 60 * 1000,
            SESSION_TIMEOUT: 24 * 60 * 60 * 1000,
            PASSWORD_MIN_LENGTH: 6,
            USERNAME_MIN_LENGTH: 3,
            MAX_INPUT_LENGTH: 255
        }),
        
        // 存储键名
        STORAGE_KEYS: Object.freeze({
            USERS: 'mzy_users',
            ACTIVATIONS: 'mzy_activations',
            COMMISSIONS: 'mzy_commissions',
            CURRENT_USER: 'mzy_currentUser',
            ADMIN_LOGS: 'mzy_adminLogs',
            LOGIN_ATTEMPTS: 'mzy_loginAttempts',
            LAST_FAILED_TIME: 'mzy_lastFailedTime',
            LAST_LOGIN_TIME: 'mzy_lastLoginTime',
            AUTO_LOGIN: 'mzy_autoLogin',
            REMEMBERED_USERNAME: 'mzy_rememberedUsername',
            ERROR_LOGS: 'mzy_errorLogs'
        }),
        
        // 状态配置
        STATUS: Object.freeze({
            PENDING: 'pending',
            APPROVED: 'approved',
            REJECTED: 'rejected',
            ACTIVE: 'active',
            INACTIVE: 'inactive'
        }),
        
        // 角色配置
        ROLES: Object.freeze({
            USER: 'user',
            ADMIN: 'admin',
            SUPER_ADMIN: 'SUPER_ADMIN',
            AUDITOR: 'AUDITOR',
            VIEWER: 'VIEWER'
        })
    });

    // 管理员权限角色定义
    const ADMIN_ROLES = Object.freeze({
        SUPER_ADMIN: {
            id: 'SUPER_ADMIN',
            name: '超级管理员',
            description: '拥有所有权限',
            permissions: ['all']
        },
        admin: {
            id: 'admin',
            name: '普通管理员',
            description: '管理用户、审核充值、查看数据',
            permissions: [
                'dashboard.view',
                'users.view', 'users.edit', 'users.delete',
                'activations.view', 'activations.approve', 'activations.reject',
                'commissions.view',
                'settings.view'
            ]
        },
        AUDITOR: {
            id: 'AUDITOR',
            name: '审核员',
            description: '负责充值审核',
            permissions: [
                'dashboard.view',
                'activations.view', 'activations.approve', 'activations.reject',
                'users.view',
                'commissions.view'
            ]
        },
        VIEWER: {
            id: 'VIEWER',
            name: '观察员',
            description: '只能查看数据',
            permissions: [
                'dashboard.view',
                'users.view',
                'activations.view',
                'commissions.view'
            ]
        }
    });

    // 操作类型定义
    const ADMIN_ACTIONS = Object.freeze({
        LOGIN: 'login',
        LOGOUT: 'logout',
        VIEW_USER: 'view_user',
        EDIT_USER: 'edit_user',
        DELETE_USER: 'delete_user',
        APPROVE_ACTIVATION: 'approve_activation',
        REJECT_ACTIVATION: 'reject_activation',
        VIEW_COMMISSION: 'view_commission',
        EDIT_SETTING: 'edit_setting',
        ACCESS_DENIED: 'access_denied'
    });

    // 导出到全局
    global.SYSTEM_CONFIG = SYSTEM_CONFIG;
    global.ADMIN_ROLES = ADMIN_ROLES;
    global.ADMIN_ACTIONS = ADMIN_ACTIONS;

})(typeof window !== 'undefined' ? window : this);
