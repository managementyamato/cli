# Dependency Audit Report
**Date:** 2026-01-05
**Project:** 現場管理システム (Construction Site Management System)
**Branch:** claude/audit-dependencies-mk0uc1heu3tc90mg-wFx3f

## Executive Summary

This is a static HTML application with **no external package dependencies**. All code is self-contained in a single 73KB `index.html` file. While this eliminates dependency management overhead, it introduces significant security vulnerabilities, maintainability issues, and performance concerns.

**Critical Issues Found:**
- 🔴 **HIGH PRIORITY:** Multiple XSS vulnerabilities
- 🟡 **MEDIUM PRIORITY:** Code bloat and performance issues
- 🟡 **MEDIUM PRIORITY:** No build process or code optimization
- 🟢 **LOW PRIORITY:** Missing modern web features

---

## 1. Security Vulnerabilities (CRITICAL)

### 1.1 Cross-Site Scripting (XSS) - Multiple Locations

**Severity:** 🔴 CRITICAL

The application has widespread XSS vulnerabilities due to unsafe use of `innerHTML` with unsanitized user input.

**Vulnerable Code Locations:**

| Line | Function | Vulnerability |
|------|----------|---------------|
| 1033-1037 | `pj-number` input handler | PJ suggestions using `p.id` and `p.name` |
| 1041 | `pj-number` input handler | Unsafe HTML in warning message |
| 1193-1216 | `renderTroubleList()` | Table rendering with `t.content`, `t.solution`, `t.pjName` |
| 1220-1243 | `renderTroubleList()` | Mobile cards with unsanitized data |
| 1279-1293 | `renderStats()` | Device stats rendering |
| 1317-1367 | `showDetail()` | Modal detail view |
| 1298-1305 | `renderStats()` | Recent troubles rendering |
| 1748-1760 | `renderPjMasterTable()` | PJ master table |
| 1767-1772 | `renderAssigneeList()` | Assignee list |

**Attack Vector Example:**
```javascript
// An attacker could inject malicious code through:
// 1. Project name: <img src=x onerror=alert('XSS')>
// 2. Trouble content: <script>/* malicious code */</script>
// 3. CSV import with malicious data
```

**Recommendation:**
1. **Immediate Fix:** Add DOMPurify library for HTML sanitization
2. **Use textContent** instead of innerHTML where possible
3. **Implement Content Security Policy (CSP)** headers

### 1.2 localStorage Security Issues

**Severity:** 🟡 MEDIUM

- Sensitive data stored in localStorage (line 846, 960-971) without encryption
- No access control or data validation
- Data persists across sessions and can be accessed by any script

**Recommendation:**
- Consider IndexedDB for larger datasets
- Implement data validation on read
- Add data versioning to handle schema changes
- Consider encryption for sensitive data (if applicable)

### 1.3 File Upload Vulnerabilities

**Severity:** 🟡 MEDIUM

**Location:** index.html:599

```html
<input type="file" id="file-input" accept="image/*" multiple hidden>
```

**Issues:**
- No file size validation
- No file type validation beyond browser `accept` attribute
- No malware scanning
- Files read as base64 but never validated

**Recommendation:**
- Add client-side file size limits
- Validate file types via magic numbers
- Limit number of concurrent uploads
- Consider server-side processing for production

### 1.4 CSV Injection Risks

**Severity:** 🟡 MEDIUM

**Locations:** index.html:1566-1621, 1624-1733

CSV import functionality could be exploited:
- Formula injection (e.g., `=cmd|'/c calc'!A1`)
- No validation of imported data structure
- Automatic execution of imported reporter/assignee names

**Recommendation:**
- Sanitize CSV cell values starting with `=`, `+`, `-`, `@`
- Validate data types and ranges
- Preview import before applying changes
- Add rollback functionality

---

## 2. Code Bloat & Performance Issues

### 2.1 File Size Analysis

| Component | Lines | Size | Percentage |
|-----------|-------|------|------------|
| CSS | 536 | ~15KB | 20% |
| JavaScript | 1016 | ~40KB | 55% |
| HTML | 311 | ~18KB | 25% |
| **Total** | **1863** | **~73KB** | **100%** |

**Issues:**
- Single monolithic file
- No code splitting or lazy loading
- All code loads on initial page load
- No minification or compression

### 2.2 Dummy Data Bloat

**Location:** index.html:849-956 (153 lines)

Hardcoded initial data adds unnecessary weight. This should be:
- Moved to a separate JSON file
- Loaded conditionally (only on first run)
- Reduced to minimal examples

**Savings:** ~5-7KB

### 2.3 Repeated Code Patterns

**Duplicate Logic:**
1. CSV parsing (lines 1525-1563)
2. Modal open/close (lines 1487-1493)
3. Date formatting (lines 1477-1485)
4. Similar rendering patterns for tables/cards
5. Status class mapping (lines 1468-1475)

**Recommendation:** Refactor into reusable utility functions

### 2.4 Inefficient DOM Manipulation

**Issues:**
- Using `innerHTML` for entire lists (re-renders everything)
- No virtual DOM or efficient diffing
- Event listeners recreated on every render
- No memoization of computed values

**Recommendation:**
- Use DocumentFragment for batch DOM updates
- Implement event delegation
- Consider a lightweight reactive library (Alpine.js, Petite Vue)

### 2.5 No Build Process

**Missing Optimizations:**
- No minification (could save 30-40%)
- No tree-shaking
- No code splitting
- No asset optimization
- No TypeScript/ESLint for code quality

---

## 3. Missing External Dependencies (Opportunities)

### 3.1 Security Libraries

| Library | Purpose | Size | Priority |
|---------|---------|------|----------|
| **DOMPurify** | XSS sanitization | ~20KB (min+gzip) | 🔴 CRITICAL |
| **crypto-js** | Data encryption (if needed) | ~40KB | 🟡 MEDIUM |

### 3.2 Utility Libraries

| Library | Purpose | Size | Priority |
|---------|---------|------|----------|
| **PapaParse** | Better CSV parsing | ~30KB | 🟡 MEDIUM |
| **date-fns** | Date formatting | ~13KB (modular) | 🟢 LOW |
| **idb** | IndexedDB wrapper | ~3KB | 🟢 LOW |

### 3.3 UI/Framework Options

| Library | Purpose | Size | Priority |
|---------|---------|------|----------|
| **Alpine.js** | Lightweight reactivity | ~15KB | 🟡 MEDIUM |
| **Petite Vue** | Minimal Vue alternative | ~6KB | 🟡 MEDIUM |
| **Tailwind CSS** | Utility-first CSS | ~10KB (purged) | 🟢 LOW |

### 3.4 Development Tools

| Tool | Purpose | Priority |
|------|---------|----------|
| **Vite** | Build tool & dev server | 🟡 MEDIUM |
| **ESLint** | Code quality | 🟡 MEDIUM |
| **Prettier** | Code formatting | 🟢 LOW |
| **TypeScript** | Type safety | 🟢 LOW |

---

## 4. Recommended Changes

### Phase 1: Critical Security Fixes (IMMEDIATE)

1. **Add DOMPurify for XSS Protection**
```html
<!-- Add before closing </head> -->
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.8/dist/purify.min.js"></script>
```

2. **Sanitize All User Input**
```javascript
// Replace innerHTML with:
element.innerHTML = DOMPurify.sanitize(userInput);
```

3. **Add Content Security Policy**
```html
<meta http-equiv="Content-Security-Policy" content="
  default-src 'self';
  script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
  style-src 'self' 'unsafe-inline';
">
```

4. **Validate File Uploads**
```javascript
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

function validateFile(file) {
  if (file.size > MAX_FILE_SIZE) {
    throw new Error('File too large');
  }
  if (!ALLOWED_TYPES.includes(file.type)) {
    throw new Error('Invalid file type');
  }
}
```

### Phase 2: Code Optimization (SHORT TERM)

1. **Split into Separate Files**
```
project/
├── index.html
├── styles.css
├── app.js
└── data.js
```

2. **Extract Dummy Data**
```javascript
// Move to data.js or load conditionally
const initialData = { /* ... */ };
```

3. **Add Build Process**
```bash
npm init -y
npm install --save-dev vite
```

```json
// package.json scripts
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  }
}
```

4. **Implement Code Splitting**
```javascript
// Load CSV parser only when needed
const loadCSVParser = async () => {
  const module = await import('./csvParser.js');
  return module.parseCSV;
};
```

### Phase 3: Feature Enhancements (LONG TERM)

1. **Add Progressive Web App (PWA) Support**
   - Service worker for offline functionality
   - App manifest for installability
   - Cache strategies for assets

2. **Implement Better State Management**
   - Consider Alpine.js or Petite Vue
   - Add data validation layer
   - Implement undo/redo functionality

3. **Improve CSV Handling**
   - Use PapaParse for robust parsing
   - Add import preview
   - Implement data mapping UI

4. **Add Data Export**
   - JSON export
   - PDF reports
   - Excel/CSV export with proper formatting

---

## 5. Implementation Priority

### 🔴 CRITICAL (Implement Immediately)

- [ ] Add DOMPurify library
- [ ] Sanitize all `innerHTML` assignments
- [ ] Add CSP meta tag
- [ ] Validate file uploads
- [ ] Sanitize CSV imports

### 🟡 HIGH (Implement Within 2 Weeks)

- [ ] Split code into separate files
- [ ] Add build process (Vite)
- [ ] Minify and optimize assets
- [ ] Extract dummy data
- [ ] Add input validation layer

### 🟢 MEDIUM (Implement Within 1 Month)

- [ ] Consider lightweight framework (Alpine.js)
- [ ] Improve CSV handling (PapaParse)
- [ ] Add unit tests
- [ ] Implement proper error handling
- [ ] Add data export functionality

### ⚪ LOW (Future Enhancements)

- [ ] TypeScript migration
- [ ] PWA support
- [ ] Advanced analytics
- [ ] Multi-language support
- [ ] Server-side sync

---

## 6. Estimated Impact

### Security Improvements

| Action | Risk Reduction | Effort |
|--------|----------------|--------|
| Add DOMPurify | 90% XSS risk | 1 hour |
| CSP headers | 70% injection risk | 30 min |
| File validation | 60% upload risk | 2 hours |
| CSV sanitization | 80% injection risk | 1 hour |

### Performance Improvements

| Action | Performance Gain | Effort |
|--------|------------------|--------|
| Code splitting | 40% initial load | 4 hours |
| Minification | 30-40% file size | 1 hour |
| Lazy loading | 50% initial JS | 6 hours |
| Build process | Overall 50% | 8 hours |

### Maintainability Improvements

| Action | Benefit | Effort |
|--------|---------|--------|
| File separation | High | 4 hours |
| Add ESLint | High | 2 hours |
| TypeScript | Very High | 16 hours |
| Unit tests | High | 12 hours |

---

## 7. Cost-Benefit Analysis

### Current State
- **Pros:** No dependency management, simple deployment, works offline
- **Cons:** Security vulnerabilities, hard to maintain, poor performance

### Recommended State (After Phase 1 & 2)
- **Pros:** Secure, optimized, maintainable, modern workflow
- **Cons:** Requires build step, slightly more complex deployment
- **Net Benefit:** Significant improvement in security and maintainability

### Minimal Viable Security (Quick Fix)

If time is limited, implement **only** these changes:

1. Add DOMPurify via CDN (5 minutes)
2. Replace 10 most critical `innerHTML` calls (1 hour)
3. Add file size validation (15 minutes)
4. Add CSV formula sanitization (30 minutes)

**Total Time:** ~2 hours
**Risk Reduction:** ~70%

---

## 8. Conclusion

This application currently has:
- ✅ **Zero outdated dependencies** (because there are none)
- ❌ **Critical security vulnerabilities** (XSS, injection risks)
- ❌ **Significant code bloat** (73KB monolithic file)
- ❌ **No optimization** (no minification, no code splitting)

### Immediate Action Required

The **XSS vulnerabilities** pose the greatest risk and should be addressed immediately. Even without adding a build process, you can:

1. Add DOMPurify via CDN (no build required)
2. Replace unsafe `innerHTML` with sanitized versions
3. Add basic input validation

### Long-term Strategy

For long-term maintainability, consider:
1. Implementing a simple build process (Vite)
2. Splitting code into modules
3. Adding a lightweight framework for reactivity
4. Implementing proper testing

The current "no dependencies" approach is not inherently bad, but it requires **extreme care** with security. The recommended changes balance simplicity with modern best practices.

---

## Appendix: Sample Package.json (Optional)

If you decide to add a build process:

```json
{
  "name": "construction-site-management",
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview",
    "lint": "eslint .",
    "format": "prettier --write ."
  },
  "devDependencies": {
    "vite": "^5.0.0",
    "eslint": "^8.56.0",
    "prettier": "^3.1.1"
  },
  "dependencies": {
    "dompurify": "^3.0.8"
  }
}
```

**Note:** This would increase the project complexity but significantly improve maintainability and security.
