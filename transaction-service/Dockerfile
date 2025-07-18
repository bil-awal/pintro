# Build stage
FROM golang:1.19-alpine AS builder

# Set working directory
WORKDIR /app

# Install dependencies
RUN apk add --no-cache git ca-certificates

# Copy go mod and sum files
COPY go.mod go.sum ./

# Download dependencies
RUN go mod download

# Copy source code
COPY . .

# Build the application
RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o main ./cmd/server

# Final stage
FROM alpine:latest

# Install ca-certificates for HTTPS requests
RUN apk --no-cache add ca-certificates

# Create non-root user
RUN addgroup -g 1001 appgroup && adduser -u 1001 -G appgroup -s /bin/sh -D appuser

# Set working directory
WORKDIR /root/

# Copy the binary from builder stage
COPY --from=builder /app/main .

# Copy migration files
COPY --from=builder /app/migrations ./migrations

# Change ownership to non-root user
RUN chown -R appuser:appgroup /root/

# Switch to non-root user
USER appuser

# Expose port
EXPOSE 8080

# Command to run the application
CMD ["./main"]
