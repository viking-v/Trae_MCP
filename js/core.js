/**
 * 梦之缘创业投资平台 - 核心模块
 * @version 3.0
 * @description 包含用户管理、缓存、安全验证、分润计算等核心功能
 */

(function(global) {
    'use strict';

    // ==================== 工具函数 ====================
    const Utils = {
        // 生成唯一ID
        generateId: () => Date.now() + Math.floor(Math.random() * 10000),
        
        // 生成邀请码
        generateInviteCode: () => {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = 'INV';
            for (let i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return code;
        },
        
        // 生成多个邀请码
        generateInviteCodes: (count = 5) => {
            const codes = new Set();
            while (codes.size < count) {
                codes.add(Utils.generateInviteCode());
            }
            return Array.from(codes);
        },
        
        // 格式化货币
        formatCurrency: (amount) => {
            return `${SYSTEM_CONFIG.CURRENCY_SYMBOL}${parseFloat(amount).toFixed(2)}`;
        },
        
        // 格式化日期
        formatDate: (date) => {
            const d = new Date(date);
            return d.toLocaleString('zh-CN');
        },
        
        // 获取状态文本
        getStatusText: (status) => {
            const map = {
                'pending': '待审核',
                'approved': '已通过',
                'rejected': '已拒绝',
                'active': '已激活',
                'inactive': '未激活'
            };
            return map[status] || status;
        },
        
        // HTML转义防XSS
        escapeHtml: (text) => {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        },
        
        // 深拷贝
        deepClone: (obj) => {
            if (obj === null || typeof obj !== 'object') return obj;
            if (obj instanceof Date) return new Date(obj.getTime());
            if (Array.isArray(obj)) return obj.map(item => Utils.deepClone(item));
            const cloned = {};
            for (let key in obj) {
                if (obj.hasOwnProperty(key)) {
                    cloned[key] = Utils.deepClone(obj[key]);
                }
            }
            return cloned;
        },
        
        // 防抖
        debounce: (func, wait) => {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        },
        
        // 节流
        throttle: (func, limit) => {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
    };

    // ==================== 数据管理器 ====================
    const DataManager = {
        getData: (key, defaultValue = []) => {
            try {
                const data = localStorage.getItem(key);
                return data ? JSON.parse(data) : Utils.deepClone(defaultValue);
            } catch (e) {
                console.error('DataManager.getData error:', e);
                return Utils.deepClone(defaultValue);
            }
        },
        
        setData: (key, data) => {
            try {
                localStorage.setItem(key, JSON.stringify(data));
                return true;
            } catch (e) {
                console.error('DataManager.setData error:', e);
                return false;
            }
        },
        
        removeData: (key) => {
            try {
                localStorage.removeItem(key);
                return true;
            } catch (e) {
                return false;
            }
        }
    };

    // ==================== 缓存管理器 ====================
    const CacheManager = {
        caches: new Map(),
        
        set: (key, data, ttl = 300000) => {
            CacheManager.caches.set(key, {
                data,
                expiry: Date.now() + ttl
            });
        },
        
        get: (key) => {
            const cached = CacheManager.caches.get(key);
            if (!cached) return null;
            if (Date.now() > cached.expiry) {
                CacheManager.caches.delete(key);
                return null;
            }
            return cached.data;
        },
        
        clear: (key) => {
            if (key) {
                CacheManager.caches.delete(key);
            } else {
                CacheManager.caches.clear();
            }
        },
        
        invalidatePattern: (pattern) => {
            for (const key of CacheManager.caches.keys()) {
                if (key.includes(pattern)) {
                    CacheManager.caches.delete(key);
                }
            }
        }
    };

    // ==================== 用户管理器 ====================
    const UserManager = {
        userIndex: new Map(),
        usernameIndex: new Map(),
        inviteCodeIndex: new Map(),
        
        // 初始化索引
        initIndex: () => {
            const users = DataManager.getData(SYSTEM_CONFIG.STORAGE_KEYS.USERS);
            UserManager.userIndex.clear();
            UserManager.usernameIndex.clear();
            UserManager.inviteCodeIndex.clear();
            
            users.forEach(user => {
                UserManager.userIndex.set(user.id, user);
                UserManager.usernameIndex.set(user.username, user);
                if (user.inviteCodes) {
                    user.inviteCodes.forEach(code => {
                        UserManager.inviteCodeIndex.set(code, user);
                    });
                }
            });
        },
        
        // 获取所有用户
        getAllUsers: () => {
            return DataManager.getData(SYSTEM_CONFIG.STORAGE_KEYS.USERS);
        },
        
        // 通过ID获取用户
        getUser: (userId) => {
            if (UserManager.userIndex.size === 0) {
                UserManager.initIndex();
            }
            return UserManager.userIndex.get(userId) || null;
        },
        
        // 通过用户名获取用户
        getUserByUsername: (username) => {
            if (UserManager.usernameIndex.size === 0) {
                UserManager.initIndex();
            }
            return UserManager.usernameIndex.get(username) || null;
        },
        
        // 通过邀请码获取用户
        getUserByInviteCode: (code) => {
            if (UserManager.inviteCodeIndex.size === 0) {
                UserManager.initIndex();
            }
            return UserManager.inviteCodeIndex.get(code) || null;
        },
        
        // 添加用户
        addUser: (userData) => {
            const users = UserManager.getAllUsers();
            const newUser = {
                id: Utils.generateId(),
                username: userData.username,
                password: userData.password,
                role: userData.role || 'user',
                activationStatus: userData.activationStatus || 'pending',
                walletAddress: userData.walletAddress || '',
                inviteCodes: Utils.generateInviteCodes(),
                referredUsers: [],
                createdAt: new Date().toISOString(),
                updatedAt: new Date().toISOString()
            };
            
            users.push(newUser);
            DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.USERS, users);
            
            // 更新索引
            UserManager.userIndex.set(newUser.id, newUser);
            UserManager.usernameIndex.set(newUser.username, newUser);
            newUser.inviteCodes.forEach(code => {
                UserManager.inviteCodeIndex.set(code, newUser);
            });
            
            CacheManager.invalidatePattern('team_');
            return newUser;
        },
        
        // 更新用户
        updateUser: (userId, updates) => {
            const users = UserManager.getAllUsers();
            const index = users.findIndex(u => u.id === userId);
            if (index === -1) return null;
            
            users[index] = {
                ...users[index],
                ...updates,
                updatedAt: new Date().toISOString()
            };
            
            DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.USERS, users);
            UserManager.userIndex.set(userId, users[index]);
            
            CacheManager.invalidatePattern('team_');
            return users[index];
        },
        
        // 验证登录
        validateLogin: (username, password) => {
            const user = UserManager.getUserByUsername(username);
            if (!user) {
                return { valid: false, error: '用户不存在' };
            }
            if (user.password !== password) {
                return { valid: false, error: '密码错误' };
            }
            return { valid: true, user };
        },
        
        // 计算团队大小
        calculateTeamSize: (userId, maxDepth = 7, currentDepth = 0, visited = new Set()) => {
            const cacheKey = `team_size_${userId}`;
            const cached = CacheManager.get(cacheKey);
            if (cached !== null && currentDepth === 0) return cached;
            
            if (currentDepth >= maxDepth || visited.has(userId)) return 0;
            visited.add(userId);
            
            const user = UserManager.getUser(userId);
            if (!user || !user.referredUsers || user.referredUsers.length === 0) return 0;
            
            let total = user.referredUsers.length;
            user.referredUsers.forEach(refId => {
                total += UserManager.calculateTeamSize(refId, maxDepth, currentDepth + 1, visited);
            });
            
            if (currentDepth === 0) {
                CacheManager.set(cacheKey, total, 60000);
            }
            return total;
        },
        
        // 计算最大深度
        calculateMaxDepth: (userId, maxDepth = 7, currentDepth = 0, visited = new Set()) => {
            if (currentDepth >= maxDepth || visited.has(userId)) return currentDepth;
            visited.add(userId);
            
            const user = UserManager.getUser(userId);
            if (!user || !user.referredUsers || user.referredUsers.length === 0) {
                return currentDepth;
            }
            
            let max = currentDepth;
            user.referredUsers.forEach(refId => {
                const depth = UserManager.calculateMaxDepth(refId, maxDepth, currentDepth + 1, visited);
                max = Math.max(max, depth);
            });
            return max;
        },
        
        // 获取上级链
        getUplineChain: (userId, maxDepth = 7) => {
            const users = UserManager.getAllUsers();
            const chain = [];
            let currentUser = UserManager.getUser(userId);
            
            while (currentUser && chain.length < maxDepth) {
                const inviter = users.find(u => 
                    u.referredUsers && u.referredUsers.includes(currentUser.id)
                );
                if (inviter) {
                    chain.push(inviter);
                    currentUser = inviter;
                } else {
                    break;
                }
            }
            return chain;
        }
    };

    // ==================== 分润计算器 ====================
    const ProfitCalculator = {
        // 计算七级分润
        calculateSevenLevelProfit: (amount) => {
            const profits = [];
            let totalProfit = 0;
            
            SYSTEM_CONFIG.PROFIT_RATES.forEach((rate, index) => {
                const profitAmount = Math.round(amount * rate * 100) / 100;
                totalProfit += profitAmount;
                profits.push({
                    level: index + 1,
                    rate,
                    amount: profitAmount,
                    percentage: (rate * 100).toFixed(1)
                });
            });
            
            return {
                profits,
                totalProfit: Math.round(totalProfit * 100) / 100,
                remainingAmount: Math.round((amount - totalProfit) * 100) / 100
            };
        },
        
        // 生成分润记录
        generateCommissions: (userId, amount) => {
            const uplines = UserManager.getUplineChain(userId);
            const commissions = DataManager.getData(SYSTEM_CONFIG.STORAGE_KEYS.COMMISSIONS);
            
            uplines.forEach((upline, index) => {
                if (index < SYSTEM_CONFIG.MAX_LEVEL_DEPTH) {
                    const rate = SYSTEM_CONFIG.PROFIT_RATES[index];
                    const commission = {
                        id: Utils.generateId() + index,
                        fromUserId: userId,
                        toUserId: upline.id,
                        level: index + 1,
                        rate,
                        amount: Math.round(amount * rate * 100) / 100,
                        createdAt: new Date().toISOString()
                    };
                    commissions.push(commission);
                }
            });
            
            DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.COMMISSIONS, commissions);
            return commissions;
        },
        
        // 获取用户分润
        getUserCommissions: (userId) => {
            const commissions = DataManager.getData(SYSTEM_CONFIG.STORAGE_KEYS.COMMISSIONS);
            return commissions.filter(c => c.toUserId === userId);
        },
        
        // 计算用户总收益
        calculateUserEarnings: (userId) => {
            const commissions = ProfitCalculator.getUserCommissions(userId);
            return commissions.reduce((sum, c) => sum + c.amount, 0);
        }
    };

    // ==================== 安全验证器 ====================
    const SecurityValidator = {
        // 验证用户输入
        validateInput: (input, type = 'text') => {
            if (!input || typeof input !== 'string') {
                return { valid: false, error: '输入不能为空' };
            }
            
            const sanitized = Utils.escapeHtml(input.trim());
            
            if (sanitized.length === 0) {
                return { valid: false, error: '输入不能为空' };
            }
            
            if (sanitized.length > SYSTEM_CONFIG.SECURITY.MAX_INPUT_LENGTH) {
                return { valid: false, error: '输入过长' };
            }
            
            switch (type) {
                case 'username':
                    if (sanitized.length < SYSTEM_CONFIG.SECURITY.USERNAME_MIN_LENGTH) {
                        return { valid: false, error: `用户名至少${SYSTEM_CONFIG.SECURITY.USERNAME_MIN_LENGTH}个字符` };
                    }
                    if (!/^[a-zA-Z0-9_\u4e00-\u9fa5]+$/.test(sanitized)) {
                        return { valid: false, error: '用户名只能包含字母、数字、下划线或中文' };
                    }
                    break;
                    
                case 'password':
                    if (sanitized.length < SYSTEM_CONFIG.SECURITY.PASSWORD_MIN_LENGTH) {
                        return { valid: false, error: `密码至少${SYSTEM_CONFIG.SECURITY.PASSWORD_MIN_LENGTH}个字符` };
                    }
                    break;
                    
                case 'wallet':
                    if (sanitized && !/^T[1-9A-HJ-NP-Za-km-z]{25,34}$/.test(sanitized)) {
                        return { valid: false, error: '请输入有效的TRC-20钱包地址' };
                    }
                    break;
            }
            
            return { valid: true, value: sanitized };
        },
        
        // 验证文件上传
        validateFile: (file, options = {}) => {
            const maxSize = options.maxSize || 5 * 1024 * 1024;
            const allowedTypes = options.allowedTypes || ['image/jpeg', 'image/png', 'image/gif'];
            
            if (!file) {
                return { valid: false, error: '请选择文件' };
            }
            if (!allowedTypes.includes(file.type)) {
                return { valid: false, error: '不支持的文件格式' };
            }
            if (file.size > maxSize) {
                return { valid: false, error: '文件过大' };
            }
            return { valid: true };
        }
    };

    // ==================== 权限管理器 ====================
    const PermissionManager = {
        // 检查权限
        hasPermission: (user, permission) => {
            if (!user || !user.role) return false;
            const role = ADMIN_ROLES[user.role];
            if (!role) return false;
            if (role.permissions.includes('all')) return true;
            return role.permissions.includes(permission);
        },
        
        // 检查任一权限
        hasAnyPermission: (user, permissions) => {
            return permissions.some(p => PermissionManager.hasPermission(user, p));
        },
        
        // 检查所有权限
        hasAllPermissions: (user, permissions) => {
            return permissions.every(p => PermissionManager.hasPermission(user, p));
        },
        
        // 获取角色信息
        getRoleInfo: (user) => {
            if (!user || !user.role) return null;
            return ADMIN_ROLES[user.role] || null;
        },
        
        // 记录操作日志
        logAction: (adminId, action, targetType, targetId, details = {}) => {
            const logs = DataManager.getData(SYSTEM_CONFIG.STORAGE_KEYS.ADMIN_LOGS);
            const log = {
                id: Utils.generateId(),
                adminId,
                action,
                targetType,
                targetId,
                details,
                timestamp: new Date().toISOString()
            };
            logs.unshift(log);
            if (logs.length > 1000) logs.splice(1000);
            DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.ADMIN_LOGS, logs);
        }
    };

    // ==================== 会话管理器 ====================
    const SessionManager = {
        // 获取当前用户
        getCurrentUser: () => {
            return DataManager.getData(SYSTEM_CONFIG.STORAGE_KEYS.CURRENT_USER, null);
        },
        
        // 设置当前用户
        setCurrentUser: (user) => {
            DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.CURRENT_USER, user);
            DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.LAST_LOGIN_TIME, Date.now().toString());
        },
        
        // 清除会话
        clearSession: () => {
            DataManager.removeData(SYSTEM_CONFIG.STORAGE_KEYS.CURRENT_USER);
            DataManager.removeData(SYSTEM_CONFIG.STORAGE_KEYS.LAST_LOGIN_TIME);
            DataManager.removeData(SYSTEM_CONFIG.STORAGE_KEYS.AUTO_LOGIN);
        },
        
        // 检查会话有效性
        isSessionValid: () => {
            const lastLogin = DataManager.getData(SYSTEM_CONFIG.STORAGE_KEYS.LAST_LOGIN_TIME, null);
            if (!lastLogin) return false;
            const elapsed = Date.now() - parseInt(lastLogin);
            return elapsed < SYSTEM_CONFIG.SECURITY.SESSION_TIMEOUT;
        },
        
        // 检查账号锁定
        isAccountLocked: () => {
            const attempts = parseInt(DataManager.getData(SYSTEM_CONFIG.STORAGE_KEYS.LOGIN_ATTEMPTS, '0'));
            const lastFailed = DataManager.getData(SYSTEM_CONFIG.STORAGE_KEYS.LAST_FAILED_TIME, null);
            
            if (attempts >= SYSTEM_CONFIG.SECURITY.MAX_LOGIN_ATTEMPTS && lastFailed) {
                const elapsed = Date.now() - parseInt(lastFailed);
                return elapsed < SYSTEM_CONFIG.SECURITY.LOCKOUT_DURATION;
            }
            return false;
        },
        
        // 记录登录失败
        recordLoginFailure: () => {
            const attempts = parseInt(DataManager.getData(SYSTEM_CONFIG.STORAGE_KEYS.LOGIN_ATTEMPTS, '0'));
            DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.LOGIN_ATTEMPTS, (attempts + 1).toString());
            DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.LAST_FAILED_TIME, Date.now().toString());
        },
        
        // 清除登录失败记录
        clearLoginFailures: () => {
            DataManager.removeData(SYSTEM_CONFIG.STORAGE_KEYS.LOGIN_ATTEMPTS);
            DataManager.removeData(SYSTEM_CONFIG.STORAGE_KEYS.LAST_FAILED_TIME);
        }
    };

    // ==================== 错误处理器 ====================
    const ErrorHandler = {
        handle: (error, context = '') => {
            console.error(`[${context}]`, error);
            
            const logs = DataManager.getData(SYSTEM_CONFIG.STORAGE_KEYS.ERROR_LOGS);
            logs.unshift({
                message: error.message || String(error),
                context,
                timestamp: new Date().toISOString()
            });
            if (logs.length > 100) logs.splice(100);
            DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.ERROR_LOGS, logs);
            
            return error.message || '操作失败';
        }
    };

    // ==================== 初始化函数 ====================
    const initializeApp = () => {
        try {
            // 初始化用户索引
            UserManager.initIndex();
            
            // 检查并初始化管理员账户
            const users = UserManager.getAllUsers();
            if (users.length === 0 || !users.find(u => u.role === 'admin')) {
                const adminUser = {
                    id: 1,
                    username: 'admin',
                    password: 'admin123',
                    role: 'admin',
                    activationStatus: 'active',
                    walletAddress: '',
                    inviteCodes: [],
                    referredUsers: [],
                    createdAt: new Date().toISOString(),
                    updatedAt: new Date().toISOString()
                };
                const allUsers = users.length > 0 ? users : [];
                allUsers.push(adminUser);
                DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.USERS, allUsers);
                UserManager.initIndex();
            }
            
            // 初始化其他数据
            if (!localStorage.getItem(SYSTEM_CONFIG.STORAGE_KEYS.ACTIVATIONS)) {
                DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.ACTIVATIONS, []);
            }
            if (!localStorage.getItem(SYSTEM_CONFIG.STORAGE_KEYS.COMMISSIONS)) {
                DataManager.setData(SYSTEM_CONFIG.STORAGE_KEYS.COMMISSIONS, []);
            }
            
            console.log('梦之缘平台核心模块初始化完成 v3.0');
            return true;
        } catch (error) {
            ErrorHandler.handle(error, 'initializeApp');
            return false;
        }
    };

    // ==================== 导出到全局 ====================
    global.Utils = Utils;
    global.DataManager = DataManager;
    global.CacheManager = CacheManager;
    global.UserManager = UserManager;
    global.ProfitCalculator = ProfitCalculator;
    global.SecurityValidator = SecurityValidator;
    global.PermissionManager = PermissionManager;
    global.SessionManager = SessionManager;
    global.ErrorHandler = ErrorHandler;
    global.initializeApp = initializeApp;

    // 页面加载时自动初始化
    if (typeof document !== 'undefined') {
        document.addEventListener('DOMContentLoaded', initializeApp);
    }

})(typeof window !== 'undefined' ? window : this);
